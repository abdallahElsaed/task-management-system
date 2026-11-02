<?php

declare(strict_types=1);

namespace App\Exceptions\Projects;

use App\Constants\Projects\ProjectMessages;
use Exception;

/**
 * Thrown when a project id cannot be reached by the signed-in account, whatever
 * the reason: it belongs to another account, it never existed, or it was deleted.
 *
 * The answer is deliberately identical in all three cases, so no response ever
 * reveals that a project exists.
 */
final class ProjectNotFoundException extends Exception
{
    public function __construct(string $message = ProjectMessages::NOT_FOUND)
    {
        parent::__construct($message);
    }
}
