<?php

declare(strict_types=1);

namespace App\Constants\Projects;

/**
 * One constant per row of FT-002's Content & Microcopy table, so a wording change
 * is a single edit and every message the module can return is auditable in one place.
 *
 * Status is the one exception — ProjectStatus::invalidMessage() derives that copy
 * from its own values, so the message stays in step with what the rule accepts.
 */
final class ProjectMessages
{
    public const string NAME_REQUIRED = 'The project name is required.';

    public const string NAME_SHORT = 'The project name must be at least 3 characters.';

    public const string NAME_LONG = 'The project name may not be longer than 255 characters.';

    public const string DESCRIPTION_LONG = 'The description may not be longer than 2000 characters.';

    public const string NOT_FOUND = 'Project not found.';

    public const string DELETED = 'Project deleted successfully.';

    public const string PER_PAGE_INVALID = 'The per_page value must be between 1 and 100.';

    public const string NO_UPDATABLE_FIELDS = 'Send at least one of: name, description, status.';
}
