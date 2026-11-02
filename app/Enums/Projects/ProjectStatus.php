<?php

declare(strict_types=1);

namespace App\Enums\Projects;

/**
 * The three lifecycle states a project moves between, in any direction.
 */
enum ProjectStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Archived = 'archived';

    private const string INVALID_MESSAGE = 'The status must be one of: %s.';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The rejection message names every accepted value, so the copy can never
     * drift away from the cases the rule actually accepts (`project_status_invalid`).
     */
    public static function invalidMessage(): string
    {
        return sprintf(self::INVALID_MESSAGE, implode(', ', self::values()));
    }
}
