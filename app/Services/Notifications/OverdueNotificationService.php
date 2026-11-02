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
    /**
     * How many times one account's claim may run. Two sweeps, or a sweep and an edit
     * to the same task, can deadlock over these rows. The database abandons one of
     * them and rolls it back, so running it again is safe, and without that a sweep
     * would stop there and leave its remaining accounts for the next run.
     */
    private const int CLAIM_ATTEMPTS = 3;

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
     * Marking comes before queueing, and both land together or neither does. From
     * the moment the sweep claims these tasks no later run can find them, so a
     * second run the same day — or a manual one — has nothing to send.
     *
     * The job is pushed before the commit on purpose. Inside the transaction, a
     * queue that refuses it rolls the claim back and leaves the tasks for the next
     * sweep; held until after the commit, the same refusal would arrive once the
     * claim was permanent and the email would be lost for good.
     *
     * On the `database` queue the job row commits with the claim, so the two cannot
     * come apart. Any other driver takes the job before the commit does, so a commit
     * that fails after a successful push leaves a job for tasks that stay unclaimed,
     * and the next sweep names them again: a duplicate, never a lost email.
     *
     * The tasks are read inside the transaction, under a lock, so the check and the
     * claim are one step. Two sweeps can reach an account at once, because
     * `withoutOverlapping` only keeps scheduled runs apart: the first holds the rows
     * until it commits, and the second then finds nothing left to claim.
     *
     * Every id the digest reports comes from that same read, which is what lets the
     * job release exactly those tasks if delivery fails for good.
     */
    private function queueDigestFor(int $ownerId): bool
    {
        return DB::transaction(function () use ($ownerId): bool {
            $tasks = $this->tasks->lockUnreportedOverdueForOwner($ownerId);

            if ($tasks->isEmpty()) {
                return false;
            }

            $taskIds = $tasks->modelKeys();

            $this->tasks->markAsReported($taskIds);

            SendOverdueTasksDigest::dispatch($ownerId, $taskIds, OverdueTaskDigest::fromTasks($tasks))->beforeCommit();

            return true;
        }, self::CLAIM_ATTEMPTS);
    }
}
