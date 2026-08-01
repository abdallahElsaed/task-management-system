<?php

declare(strict_types=1);

namespace App\DTOs\Tasks;

use App\Enums\Tasks\TaskPriority;
use App\Enums\Tasks\TaskStatus;

/**
 * The four narrowing terms the task list accepts, already validated and normalised
 * by IndexTasksRequest. Every one is nullable, and null means "not filtering on
 * this", which is what lets the repository compose them with AND (BR-17).
 *
 * A search term that trimmed to nothing arrives here as null rather than as an
 * empty string, so it is an absent filter rather than an active one (EC-10).
 */
final readonly class TaskFilters
{
    public function __construct(
        public ?TaskStatus $status = null,
        public ?TaskPriority $priority = null,
        public ?int $projectId = null,
        public ?string $search = null,
    ) {}

    /**
     * Whether the caller narrowed the list at all. This is what separates the two
     * empty states from one another: an unfiltered empty page means the account
     * holds no tasks, a filtered one means nothing matched (EC-1, EC-2).
     */
    public function isActive(): bool
    {
        return $this->status !== null
            || $this->priority !== null
            || $this->projectId !== null
            || $this->search !== null;
    }
}
