<?php

declare(strict_types=1);

namespace App\DTOs\Dashboard;

/**
 * The task half of the dashboard. The four numbers are four views of the same
 * tasks rather than four slices of them: completed and pending add up to the
 * total, and overdue sits inside pending.
 */
final readonly class TaskCounts
{
    public function __construct(
        public int $total,
        public int $completed,
        public int $pending,
        public int $overdue,
    ) {}
}
