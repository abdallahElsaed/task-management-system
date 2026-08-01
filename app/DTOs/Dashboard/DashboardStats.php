<?php

declare(strict_types=1);

namespace App\DTOs\Dashboard;

/**
 * The whole dashboard payload. The two halves stay separate rather than being
 * flattened into six integers, so each repository owns its own counts and
 * neither has to know the other's shape.
 */
final readonly class DashboardStats
{
    public function __construct(
        public ProjectCounts $projects,
        public TaskCounts $tasks,
    ) {}
}
