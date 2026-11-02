<?php

declare(strict_types=1);

namespace App\Services\Tasks;

use App\Constants\Tasks\TaskMessages;
use App\Contracts\Repositories\TaskRepositoryInterface;
use App\DTOs\Tasks\TaskFilters;
use App\DTOs\Tasks\TaskPage;
use App\Enums\Tasks\TaskPriority;
use App\Enums\Tasks\TaskStatus;
use App\Exceptions\Tasks\TaskNotFoundException;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class TaskService
{
    public function __construct(private TaskRepositoryInterface $tasks) {}

    /**
     * One page of the caller's tasks across every project they own,
     * carrying whichever empty-state message it earned.
     */
    public function paginateForOwner(User $owner, TaskFilters $filters, int $perPage): TaskPage
    {
        $page = $this->tasks->paginateForOwner($owner->id, $filters, $perPage);

        return new TaskPage($page, $this->emptyStateMessage($owner, $page, $filters));
    }

    /**
     * A task created without a stated priority is Medium and without a stated
     * status is Todo. The defaults are applied here rather than left
     * to the column, so the created response carries both without a second read.
     *
     * The parent project is not checked here: StoreTaskRequest has already proved
     * the caller owns it, which is what makes another account's project a `422`
     * rather than a `404`.
     *
     * @param  array{project_id: int|string, title: string, description?: ?string, priority?: ?string, status?: ?string, due_date: string}  $data
     */
    public function create(array $data): Task
    {
        return $this->tasks->create((int) $data['project_id'], [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'] ?? TaskPriority::Medium->value,
            'status' => $data['status'] ?? TaskStatus::Todo->value,
            'due_date' => $data['due_date'],
        ]);
    }

    /**
     * The single way a task is reached, so every unreachable id — one under
     * another account's project, one that never existed, one already deleted,
     * one whose project was deleted — answers identically.
     *
     * @throws TaskNotFoundException
     */
    public function findForOwner(User $owner, int $taskId): Task
    {
        return $this->tasks->findForOwner($owner->id, $taskId)
            ?? throw new TaskNotFoundException;
    }

    /**
     * `project_id` never reaches here: UpdateTaskRequest carries no rule for it,
     * so it is dropped from the validated data.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws TaskNotFoundException
     */
    public function update(User $owner, int $taskId, array $data): Task
    {
        return $this->tasks->update($this->findForOwner($owner, $taskId), $data);
    }

    /**
     * @throws TaskNotFoundException
     */
    public function delete(User $owner, int $taskId): void
    {
        $this->tasks->delete($this->findForOwner($owner, $taskId));
    }

    /**
     * The two empty states read almost alike, and the difference matters: an account that
     * has never created a task is told so, while one whose filters matched nothing
     * is told that instead.
     *
     * The extra existence check runs only when the page came back empty, and only
     * when filters were active — so the common path costs nothing. A page past the
     * last page is not an empty state at all, because `total()` counts the whole
     * result rather than the page.
     *
     * @param  LengthAwarePaginator<int, Task>  $page
     */
    private function emptyStateMessage(User $owner, LengthAwarePaginator $page, TaskFilters $filters): ?string
    {
        if ($page->total() > 0) {
            return null;
        }

        if ($filters->isActive() && $this->tasks->existsForOwner($owner->id)) {
            return TaskMessages::NO_MATCHES;
        }

        return TaskMessages::EMPTY;
    }
}
