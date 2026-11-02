<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\DTOs\Dashboard\TaskCounts;
use App\DTOs\Tasks\TaskFilters;
use App\Models\Task;
use App\Repositories\TaskRepository;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

#[Bind(TaskRepository::class)]
interface TaskRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Task>
     */
    public function paginateForOwner(int $ownerId, TaskFilters $filters, int $perPage): LengthAwarePaginator;

    public function countsForOwner(int $ownerId): TaskCounts;

    public function existsForOwner(int $ownerId): bool;

    public function findForOwner(int $ownerId, int $taskId): ?Task;

    /**
     * @param  array{title: string, description: ?string, priority: string, status: string, due_date: string}  $attributes
     */
    public function create(int $projectId, array $attributes): Task;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Task $task, array $attributes): Task;

    public function delete(Task $task): void;

    /**
     * @return int the number of tasks removed
     */
    public function deleteAllForProject(int $projectId): int;

    /**
     * @return list<int> the id of every account holding at least one task the
     *                   overdue sweep still owes an email
     */
    public function ownerIdsWithUnreportedOverdueTasks(): array;

    /**
     * @return list<int> the account's tasks the overdue sweep still owes an email
     */
    public function unreportedOverdueIdsForOwner(int $ownerId): array;

    /**
     * Must run inside a transaction, which holds the lock until it ends.
     *
     * @param  list<int>  $taskIds
     * @return Collection<int, Task> those still unreported, longest overdue first
     */
    public function lockUnreportedOverdue(array $taskIds): Collection;

    /**
     * @param  list<int>  $taskIds
     * @return int the number of tasks marked
     */
    public function markAsReported(array $taskIds): int;

    /**
     * @param  list<int>  $taskIds
     * @return int the number of tasks released
     */
    public function clearReported(array $taskIds): int;

    /**
     * @return int the number of tasks marked
     */
    public function markAllOverdueAsReported(): int;
}
