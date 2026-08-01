<?php

declare(strict_types=1);

namespace App\DTOs\Notifications;

use App\Models\Task;
use Illuminate\Support\Collection;

/**
 * Everything one account's overdue email says, settled at sweep time and carried
 * to the worker as the queued job's payload. Building it here rather than in the
 * worker is what makes the email describe the moment the sweep ran, whenever the
 * queue gets round to sending it.
 */
final readonly class OverdueTaskDigest
{
    /**
     *  Beyond this the email counts the rest instead of naming them,
     * so one bad month cannot produce a message nobody can read.
     */
    public const int MAX_LISTED_TASKS = 50;

    /**
     * @param  list<OverdueTaskLine>  $lines  at most MAX_LISTED_TASKS of them
     * @param  int  $totalCount  every task this digest reports, listed or not
     */
    public function __construct(
        public array $lines,
        public int $totalCount,
    ) {}

    /**
     * @param  Collection<int, Task>  $tasks  longest overdue first (BR-4)
     */
    public static function fromTasks(Collection $tasks): self
    {
        return new self(
            lines: $tasks->take(self::MAX_LISTED_TASKS)
                ->map(OverdueTaskLine::fromTask(...))
                ->values()
                ->all(),
            totalCount: $tasks->count(),
        );
    }

    /**
     * The tasks this digest reports but does not name.
     */
    public function remainingCount(): int
    {
        return $this->totalCount - count($this->lines);
    }

}
