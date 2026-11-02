<?php

declare(strict_types=1);

namespace App\Constants\Tasks;

/**
 * One constant per message, so a wording change
 * is a single edit and every message the module can return is auditable in one place.
 *
 * Priority and status are the two exceptions — TaskPriority::invalidMessage() and
 * TaskStatus::invalidMessage() derive that copy from their own values, so the
 * message stays in step with what the rule accepts.
 */
final class TaskMessages
{
    public const string TITLE_REQUIRED = 'The task title is required.';

    public const string TITLE_SHORT = 'The task title must be at least 3 characters.';

    public const string TITLE_LONG = 'The task title may not be longer than 255 characters.';

    public const string DESCRIPTION_LONG = 'The description may not be longer than 2000 characters.';

    public const string DUE_DATE_REQUIRED = 'The due date is required.';

    public const string DUE_DATE_INVALID = 'The due date must be a valid date in YYYY-MM-DD format.';

    public const string PROJECT_INVALID = 'The selected project was not found.';

    public const string NOT_FOUND = 'Task not found.';

    public const string DELETED = 'Task deleted successfully.';

    public const string EMPTY = 'You have no tasks yet.';

    public const string NO_MATCHES = 'No tasks match these filters.';

    public const string SEARCH_TOO_LONG = 'The search term may not be longer than 255 characters.';

    public const string PER_PAGE_INVALID = 'The per_page value must be between 1 and 100.';

    public const string NO_UPDATABLE_FIELDS = 'Send at least one of: title, description, priority, status, due_date.';
}
