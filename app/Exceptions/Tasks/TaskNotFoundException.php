<?php

declare(strict_types=1);

namespace App\Exceptions\Tasks;

use App\Constants\Tasks\TaskMessages;
use Exception;

/**
 * Thrown when a task id cannot be reached by the signed-in account, whatever the
 * reason: it sits under another account's project, it never existed, it was
 * deleted, or its parent project was deleted.
 *
 * The answer is deliberately identical in every case, so no response ever reveals
 * that a task exists (BR-4, EC-3, EC-14, EC-20).
 */
final class TaskNotFoundException extends Exception
{
    public function __construct(string $message = TaskMessages::NOT_FOUND)
    {
        parent::__construct($message);
    }
}
