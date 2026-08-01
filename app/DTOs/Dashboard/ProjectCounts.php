<?php

declare(strict_types=1);

namespace App\DTOs\Dashboard;

/**
 * The project half of the dashboard. Both numbers describe the signed-in
 * account alone and exclude deleted projects.
 */
final readonly class ProjectCounts
{
    public function __construct(
        public int $total,
        public int $active,
    ) {}
}
