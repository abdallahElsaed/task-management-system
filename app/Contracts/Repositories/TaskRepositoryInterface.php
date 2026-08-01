<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Repositories\TaskRepository;
use Illuminate\Container\Attributes\Bind;

#[Bind(TaskRepository::class)]
interface TaskRepositoryInterface
{
    /**
     * @return int the number of tasks removed
     */
    public function deleteAllForProject(int $projectId): int;
}
