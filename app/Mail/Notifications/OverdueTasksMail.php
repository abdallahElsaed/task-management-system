<?php

declare(strict_types=1);

namespace App\Mail\Notifications;

use App\Constants\Notifications\OverdueNotificationMessages;
use App\DTOs\Notifications\OverdueTaskDigest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * The one overdue email: an account's newly overdue tasks, gathered into a
 * single message. It is not queueable itself — SendOverdueTasksDigest is
 * the queued job that carries it, which is what leaves somewhere to release
 * the tasks when every attempt has failed.
 *
 * Both constructor properties are public, so the view is handed them by name.
 */
final class OverdueTasksMail extends Mailable
{
    private const string VIEW = 'emails.notifications.overdue-tasks';

    public function __construct(
        public readonly string $recipientName,
        public readonly OverdueTaskDigest $digest,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: OverdueNotificationMessages::subject($this->digest->totalCount),
        );
    }

    public function content(): Content
    {
        return new Content(view: self::VIEW);
    }
}
