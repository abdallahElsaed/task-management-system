<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\Contracts\Repositories\TaskRepositoryInterface;
use App\DTOs\Dashboard\DashboardStats;
use App\Models\User;

final readonly class DashboardService
{
    public function __construct(
        private ProjectRepositoryInterface $projects,
        private TaskRepositoryInterface $tasks,
    ) {}

    /**
     * The counts are read outside a transaction on purpose: asks
     * that every number describe the moment the request arrived, not that all
     * six describe the same instant, and a read-only summary does not justify
     * holding a snapshot open.
     */
    public function statsForOwner(User $owner): DashboardStats
    {
        return new DashboardStats(
            $this->projects->countsForOwner($owner->id),
            $this->tasks->countsForOwner($owner->id),
        );
    }
}
