<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Notifications\OverdueNotificationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * FT-005's scheduled entry point. It owns no logic of its own: the sweep decides
 * what went late and the queue delivers it, which is what keeps a command that
 * runs unattended down to something that can be read in one go.
 *
 * Safe to run by hand at any time — a second run the same day finds nothing left
 * to report and sends nothing (EC-13).
 */
#[Signature('tasks:notify-overdue')]
#[Description('Email each account holder the tasks of theirs that have passed their due date (FT-005).')]
final class NotifyOverdueTasks extends Command
{
    /**
     * BR-1, in the application's configured timezone. `routes/console.php` reads
     * it, so the hour the product promises lives beside the command that keeps it.
     */
    public const string DAILY_RUN_TIME = '08:00';

    private const string RESULT = 'Queued an overdue digest for %d account(s).';

    public function handle(OverdueNotificationService $notifications): int
    {
        $this->info(sprintf(self::RESULT, $notifications->sweep()));

        return self::SUCCESS;
    }
}
