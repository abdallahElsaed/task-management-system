<?php

declare(strict_types=1);

namespace App\DTOs\Notifications;

use App\Models\Task;
use Carbon\CarbonImmutable;

/**
 * One line of the overdue email, carrying exactly what BR-8 says a task is named
 * by. It travels inside the queued digest, so it holds the three values read at
 * sweep time rather than a task the worker would have to load again.
 */
final readonly class OverdueTaskLine
{
    public function __construct(
        public string $title,
        public string $projectName,
        public CarbonImmutable $dueDate,
    ) {}

    public static function fromTask(Task $task): self
    {
        return new self(
            title: $task->title,
            projectName: $task->project->name,
            dueDate: $task->due_date->toImmutable(),
        );
    }
}
