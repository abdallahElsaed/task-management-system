<?php

declare(strict_types=1);

namespace App\Jobs\Notifications;

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\DTOs\Notifications\OverdueTaskDigest;
use App\Mail\Notifications\OverdueTasksMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Delivery of one account's digest, off the request path entirely: the
 * scheduled sweep queues this and finishes, and nothing an account holder does
 * ever waits on an email.
 *
 * The sweep has already marked every task in `$taskIds` as reported, which is
 * what stops a second run the same day from sending the message twice.
 * That claim is only released if delivery fails for good — see `failed`.
 */
final class SendOverdueTasksDigest implements ShouldQueue
{
    use Queueable;

    private const int MAX_ATTEMPTS = 3;

    /**
     * What the log carries when the last attempt has gone. Operator-facing, so
     * it sits here rather than beside the email's microcopy.
     */
    private const string DELIVERY_FAILED = 'Overdue task digest could not be delivered.';

    /**
     * Seconds to wait before the second and third attempts. A mail service is
     * usually either busy for a moment or down for a while, so the second wait is
     * much longer than the first.
     *
     * @var list<int>
     */
    private const array RETRY_BACKOFF = [60, 300];

    /**
     * @param  list<int>  $taskIds  every task this digest reports, including the
     *                              ones beyond the listed 50
     */
    public function __construct(
        public readonly int $ownerId,
        public readonly array $taskIds,
        public readonly OverdueTaskDigest $digest,
    ) {}

    public function tries(): int
    {
        return self::MAX_ATTEMPTS;
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return self::RETRY_BACKOFF;
    }

    /**
     * The digest goes to the address on the owning account and nowhere else.
     */
    public function handle(UserRepositoryInterface $users): void
    {
        $owner = $users->find($this->ownerId);

        if ($owner === null) {
            return;
        }

        Mail::to($owner->email)->send(new OverdueTasksMail($owner->name, $this->digest));
    }

    /**
     * Every attempt has failed, so the claim the sweep put on these tasks is
     * released and tomorrow's sweep finds them again. The queue has already
     * recorded the failure with this job's payload — the account and the attempt
     * are both in it — and the log line puts the same facts where they are read
     * first.
     *
     * The repository is resolved here rather than injected because the queue calls
     * this hook directly, without the container.
     */
    public function failed(?Throwable $exception): void
    {
        app(TaskRepositoryInterface::class)->clearReported($this->taskIds);

        Log::error(self::DELIVERY_FAILED, [
            'owner_id' => $this->ownerId,
            'task_count' => count($this->taskIds),
            'attempts' => self::MAX_ATTEMPTS,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
