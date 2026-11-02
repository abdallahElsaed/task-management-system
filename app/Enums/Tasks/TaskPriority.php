<?php

declare(strict_types=1);

namespace App\Enums\Tasks;

/**
 * The three priorities a task carries.
 */
enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    private const string INVALID_MESSAGE = 'The priority must be one of: %s.';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The rejection message names every accepted value, so the copy can never
     * drift away from the cases the rule actually accepts (`task_priority_invalid`).
     */
    public static function invalidMessage(): string
    {
        return sprintf(self::INVALID_MESSAGE, implode(', ', self::values()));
    }
}
