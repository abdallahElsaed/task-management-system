<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Models\Task;

final readonly class TaskRepository implements TaskRepositoryInterface
{
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
