<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\DTOs\Notifications\OverdueTaskDigest;
use App\Jobs\Notifications\SendOverdueTasksDigest;
use Illuminate\Support\Facades\DB;

/**
 * FT-005's daily sweep. It finds the tasks that went late without being finished,
 * claims them, and hands one digest per account to the queue — it never sends
 * anything itself, so the scheduled command finishes in the time the queries take.
 */
final readonly class OverdueNotificationService
{
    public function __construct(private TaskRepositoryInterface $tasks) {}

    /**
     * Only the accounts that owe an email are visited, so an account with nothing
     * newly overdue is never considered and cannot be sent an all-clear (BR-3).
     *
     * @return int the number of accounts a digest was queued for
     */
    public function sweep(): int
    {
        $queued = 0;

        foreach ($this->tasks->ownerIdsWithUnreportedOverdueTasks() as $ownerId) {
            $queued += (int) $this->queueDigestFor($ownerId);
        }

        return $queued;
    }

    /**
     * Marking comes before queueing, and both land together or neither does. That
     * order is what BR-5 and EC-13 rest on: from the moment the sweep claims these
     * tasks no later run can find them, so a second run the same day — or a manual
     * one — has nothing to send. A queue that refuses the job rolls the claim back
     * with it, leaving the tasks for the next sweep rather than losing the email.
     *
     * The tasks are re-read here rather than carried over from the query that
     * named the account, because the digest needs the titles and the claim needs
     * every id, and only one of those is worth reading for accounts that turn out
     * to have nothing.
     */
    private function queueDigestFor(int $ownerId): bool
    {
        $tasks = $this->tasks->unreportedOverdueForOwner($ownerId);

        if ($tasks->isEmpty()) {
            return false;
        }

        $taskIds = $tasks->modelKeys();
        $digest = OverdueTaskDigest::fromTasks($tasks);

        DB::transaction(function () use ($ownerId, $taskIds, $digest): void {
            $this->tasks->markAsReported($taskIds);

            SendOverdueTasksDigest::dispatch($ownerId, $taskIds, $digest)->afterCommit();
        });

        return true;
    }
}
