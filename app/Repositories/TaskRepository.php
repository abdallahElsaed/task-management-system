<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\DTOs\Dashboard\TaskCounts;
use App\DTOs\Tasks\TaskFilters;
use App\Enums\Tasks\TaskPriority;
use App\Enums\Tasks\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final readonly class TaskRepository implements TaskRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Task>
     */
    public function paginateForOwner(int $ownerId, TaskFilters $filters, int $perPage): LengthAwarePaginator
    {
        return Task::ownedBy($ownerId)
            ->when(
                $filters->status,
                fn (Builder $query, TaskStatus $status) => $query->withStatus($status),
            )
            ->when(
                $filters->priority,
                fn (Builder $query, TaskPriority $priority) => $query->withPriority($priority),
            )
            ->when(
                $filters->projectId,
                fn (Builder $query, int $projectId) => $query->inProject($projectId),
            )
            ->when(
                $filters->search,
                fn (Builder $query, string $search) => $query->titleContains($search),
            )
            ->dueSoonestFirst()
            ->paginate($perPage);
    }

    /**
     * `ownedBy` carries the soft-delete rule for the task and for its project, so
     * a deleted project takes its tasks out of every count in the same moment.
     * The owner's tasks are selected once and the four numbers are
     * counted off that one pass by `selectStatusCounts`, which holds the SQL for.
     *
     * An aggregate with no `group by` always answers with exactly one row, so the
     * counts are read straight off it.
     */
    public function countsForOwner(int $ownerId): TaskCounts
    {
        $counts = Task::ownedBy($ownerId)->selectStatusCounts()->toBase()->first();

        return new TaskCounts(
            total: (int) $counts->{Task::TOTAL_COUNT},
            completed: (int) $counts->{Task::COMPLETED_COUNT},
            pending: (int) $counts->{Task::PENDING_COUNT},
            overdue: (int) $counts->{Task::OVERDUE_COUNT},
        );
    }

    public function existsForOwner(int $ownerId): bool
    {
        return Task::ownedBy($ownerId)->exists();
    }

    public function findForOwner(int $ownerId, int $taskId): ?Task
    {
        return Task::ownedBy($ownerId)->find($taskId);
    }

    /**
     * @param  array{title: string, description: ?string, priority: string, status: string, due_date: string}  $attributes
     */
    public function create(int $projectId, array $attributes): Task
    {
        $task = new Task($attributes);
        $task->project_id = $projectId;
        $task->save();

        return $task;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Task $task, array $attributes): Task
    {
        $task->fill($attributes)->save();

        return $task;
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }

    /**
     * Soft deletes rewrite this into a single UPDATE, so a project holding many
     * tasks still costs one statement.
     *
     * @return int the number of tasks removed
     */
    public function deleteAllForProject(int $projectId): int
    {
        return Task::where('project_id', $projectId)->delete();
    }

    /**
     * Asked from the project side because that is where the owner lives, and
     * because the soft-delete scope on `projects` is what keeps a deleted
     * project's tasks out of the sweep. The relation carries the task's own
     * soft-delete rule, so a deleted task is out too.
     *
     * One statement for the whole sweep: the accounts that owe an email, and no
     * others, so an account with nothing newly overdue is never visited.
     *
     * @return list<int>
     */
    public function ownerIdsWithUnreportedOverdueTasks(): array
    {
        return Project::whereHas(
            'tasks',
            fn (Builder $tasks) => $tasks->overdue()->unreported(),
        )->distinct()->pluck('user_id')->all();
    }

    /**
     * Only the four columns the email is built from are read, because an account
     * that has let a hundred tasks go late still has to fit in one message.
     *
     * `dueSoonestFirst` is the longest-overdue-first order read the other way
     * round: the oldest due date is the one that has been late longest.
     *
     * The rows stay locked until the caller's transaction ends. A locking read sees
     * the latest committed rows rather than a snapshot, so a second sweep waits here
     * and then finds the tasks already reported instead of claiming them again.
     *
     * @return Collection<int, Task>
     */
    public function lockUnreportedOverdueForOwner(int $ownerId): Collection
    {
        return Task::ownedBy($ownerId)
            ->overdue()
            ->unreported()
            ->with('project:id,name')
            ->dueSoonestFirst()
            ->lockForUpdate()
            ->get(['id', 'project_id', 'title', 'due_date']);
    }

    /**
     * @param  list<int>  $taskIds
     */
    public function markAsReported(array $taskIds): int
    {
        return $this->setReportedAt($taskIds, now());
    }

    /**
     * A digest that failed every attempt leaves its tasks unreported, so
     * tomorrow's sweep finds them again.
     *
     * @param  list<int>  $taskIds
     */
    public function clearReported(array $taskIds): int
    {
        return $this->setReportedAt($taskIds, null);
    }

    /**
     *  run once when the feature is switched on. It reuses `overdue`
     * so the tasks it silences are exactly the ones the sweep would have emailed.
     */
    public function markAllOverdueAsReported(): int
    {
        return Task::overdue()->unreported()->toBase()->update(['overdue_notified_at' => now()]);
    }

    /**
     * Written through the base builder so that `updated_at` stays where it is:
     * being named in an email is not an edit the account holder made, and
     * task payload reports that timestamp.
     *
     * @param  list<int>  $taskIds
     */
    private function setReportedAt(array $taskIds, ?CarbonInterface $reportedAt): int
    {
        return Task::whereKey($taskIds)->toBase()->update(['overdue_notified_at' => $reportedAt]);
    }
}
