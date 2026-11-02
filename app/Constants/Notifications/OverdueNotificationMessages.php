<?php

declare(strict_types=1);

namespace App\Constants\Notifications;

use App\DTOs\Notifications\OverdueTaskLine;

/**
 * One constant per message, so every word the
 * overdue email can carry is auditable in one place and a wording change is a
 * single edit.
 *
 * The rows holding a placeholder are private and reached through the method
 * beside them, so no caller can fill one in its own way.
 */
final class OverdueNotificationMessages
{
    public const string INTRO = 'The following work has passed its due date and is not marked as done:';

    public const string FOOTER = 'This message was sent automatically. Replies to it are not read.';

    /**
     * `15 Aug 2026`, the way the email writes a due date.
     */
    public const string DUE_DATE_FORMAT = 'j M Y';

    private const string SUBJECT_ONE = '1 task is past its due date';

    private const string SUBJECT_MANY = '%d tasks are past their due date';

    private const string GREETING = 'Hi %s,';

    private const string LINE = '%s — %s — was due %s';

    private const string MORE = 'and %d more.';

    private const int SINGLE_TASK = 1;

    /**
     * The count is every task that went late rather than the number of lines under
     * it, so an account with 80 newly overdue tasks reads 80 in the subject and 50
     * lines in the body.
     */
    public static function subject(int $count): string
    {
        return $count === self::SINGLE_TASK
            ? self::SUBJECT_ONE
            : sprintf(self::SUBJECT_MANY, $count);
    }

    public static function greeting(string $name): string
    {
        return sprintf(self::GREETING, $name);
    }

    /**
     * A task is named by its title, its project's name and its due date.
     */
    public static function line(OverdueTaskLine $task): string
    {
        return sprintf(
            self::LINE,
            $task->title,
            $task->projectName,
            $task->dueDate->format(self::DUE_DATE_FORMAT),
        );
    }

    public static function more(int $remaining): string
    {
        return sprintf(self::MORE, $remaining);
    }
}
