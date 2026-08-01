<?php

use App\DTOs\Notifications\OverdueTaskDigest;
use App\Mail\Notifications\OverdueTasksMail;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/*
 * BR-4: however many tasks an account let go late, it gets one message. Beyond
 * fifty the message counts the rest instead of naming them.
 */
beforeEach(function () {
    $this->travelTo('2026-07-31');

    Mail::fake();

    $this->owner = User::factory()->create();
    $this->project = Project::factory()->for($this->owner)->create();
});

/**
 * Overdue tasks whose due dates are all different, so the order they come back in
 * is provable. The first is the oldest, and therefore the longest overdue.
 *
 * @return list<string> the due dates used, oldest first
 */
function overdueTasksFor(Project $project, int $count): array
{
    $dueDates = [];

    foreach (range(1, $count) as $position) {
        $dueDate = today()->subDays($count - $position + 1)->toDateString();

        Task::factory()->for($project)->create([
            'title' => "Task {$position}",
            'due_date' => $dueDate,
        ]);

        $dueDates[] = $dueDate;
    }

    return $dueDates;
}

it('gathers forty newly overdue tasks into one email, longest overdue first', function () {
    $dueDates = overdueTasksFor($this->project, 40);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    Mail::assertSentCount(1);

    $mail = Mail::sent(OverdueTasksMail::class)->sole();

    expect($mail->digest->totalCount)->toBe(40)
        ->and($mail->digest->lines)->toHaveCount(40)
        ->and($mail->digest->hasRemaining())->toBeFalse()
        ->and($mail->digest->lines[0]->dueDate->toDateString())->toBe($dueDates[0])
        ->and($mail->digest->lines[39]->dueDate->toDateString())->toBe($dueDates[39]);

    $mail->assertDontSeeInHtml('more.');
});

it('names fifty of eighty newly overdue tasks and counts the rest', function () {
    overdueTasksFor($this->project, 80);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    Mail::assertSentCount(1);

    $mail = Mail::sent(OverdueTasksMail::class)->sole();

    expect($mail->digest->totalCount)->toBe(80)
        ->and($mail->digest->lines)->toHaveCount(OverdueTaskDigest::MAX_LISTED_TASKS)
        ->and($mail->digest->remainingCount())->toBe(30);

    $mail->assertSeeInHtml('and 30 more.');
});

it('counts every task that went late in the subject, not only the ones it names', function () {
    overdueTasksFor($this->project, 80);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    Mail::sent(OverdueTasksMail::class)->sole()
        ->assertHasSubject('80 tasks are past their due date');
});

it('says how many tasks are late in the subject of a small digest', function () {
    overdueTasksFor($this->project, 3);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    Mail::sent(OverdueTasksMail::class)->sole()
        ->assertHasSubject('3 tasks are past their due date');
});

it('marks every task it reports, including the ones it did not name', function () {
    overdueTasksFor($this->project, 80);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    expect(Task::unreported()->count())->toBe(0);
});
