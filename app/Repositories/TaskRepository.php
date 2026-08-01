<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\DTOs\Tasks\TaskFilters;
use App\Enums\Tasks\TaskPriority;
use App\Enums\Tasks\TaskStatus;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

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
     * tasks still costs one statement (BR-9).
     *
     * @return int the number of tasks removed
     */
    public function deleteAllForProject(int $projectId): int
    {
        return Task::where('project_id', $projectId)->delete();
    }
}
