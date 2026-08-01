<?php

declare(strict_types=1);

namespace App\DTOs\Tasks;

use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * One page of tasks together with the empty-state message it earned, so the rule
 * choosing between `tasks_empty` and `tasks_no_matches` lives entirely in the
 * service and the controller stays a single expression (EC-1, EC-2).
 *
 * The message is null on any page that carries results.
 */
final readonly class TaskPage
{
    /**
     * @param  LengthAwarePaginator<int, Task>  $tasks
     */
    public function __construct(
        public LengthAwarePaginator $tasks,
        public ?string $message = null,
    ) {}
}
