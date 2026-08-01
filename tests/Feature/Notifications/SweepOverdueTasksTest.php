<?php

use App\Mail\Notifications\OverdueTasksMail;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/*
 * FT-005's acceptance criteria are written against 2026-07-31, so the clock is
 * frozen on their "today" rather than the day the suite runs.
 */
beforeEach(function () {
    $this->travelTo('2026-07-31');

    Mail::fake();

    $this->owner = User::factory()->create(['name' => 'Sara']);
    $this->project = Project::factory()->for($this->owner)->create(['name' => 'Website Redesign']);
});

it('emails the owner of a task that passed its due date unfinished', function () {
    Task::factory()->for($this->project)->create(['due_date' => '2026-07-30']);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    Mail::assertSentCount(1);
    Mail::assertSent(
        OverdueTasksMail::class,
        fn (OverdueTasksMail $mail) => $mail->hasTo($this->owner->email)
            && $mail->hasSubject('1 task is past its due date'),
    );
});

it('names the task, its project and its due date the way BR-8 writes them', function () {
    Task::factory()->for($this->project)->create([
        'title' => 'Fix login bug',
        'due_date' => '2026-07-30',
    ]);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    Mail::sent(OverdueTasksMail::class)->sole()
        ->assertSeeInHtml('Hi Sara,')
        ->assertSeeInHtml('Fix login bug — Website Redesign — was due 30 Jul 2026');
});

it('says at the end of every email that no reply is read', function () {
    Task::factory()->for($this->project)->create(['due_date' => '2026-07-30']);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    Mail::sent(OverdueTasksMail::class)->sole()
        ->assertSeeInHtml('This message was sent automatically. Replies to it are not read.');
});

it('reproduces a task title character for character whatever script it was written in', function () {
    Task::factory()->for($this->project)->create([
        'title' => 'تصميم الصفحة',
        'due_date' => '2026-07-30',
    ]);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    Mail::sent(OverdueTasksMail::class)->sole()->assertSeeInHtml('تصميم الصفحة');
});

it('never sweeps a task that is only due today', function () {
    Task::factory()->for($this->project)->create(['due_date' => '2026-07-31']);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    Mail::assertNothingOutgoing();
});

it('never sweeps a task that is already done however old its due date', function () {
    Task::factory()->for($this->project)->done()->create(['due_date' => '2026-07-01']);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    Mail::assertNothingOutgoing();
});

it('never sweeps a deleted task', function () {
    Task::factory()->for($this->project)->create(['due_date' => '2026-07-30'])->delete();

    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    Mail::assertNothingOutgoing();
});

it('sends nothing to an account that holds no tasks at all', function () {
    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    Mail::assertNothingOutgoing();
});

it('sends each account only its own tasks, and nothing to an account with none', function () {
    $other = User::factory()->create();
    $otherProject = Project::factory()->for($other)->create(['name' => 'Mobile App']);
    Task::factory()->for($otherProject)->create([
        'title' => 'Ship the beta',
        'due_date' => '2026-07-30',
    ]);

    Task::factory()->for($this->project)->create([
        'title' => 'Fix login bug',
        'due_date' => '2026-08-30',
    ]);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    Mail::assertSentCount(1);
    Mail::assertNotSent(
        OverdueTasksMail::class,
        fn (OverdueTasksMail $mail) => $mail->hasTo($this->owner->email),
    );

    Mail::sent(OverdueTasksMail::class)->sole()
        ->assertSeeInHtml('Ship the beta')
        ->assertDontSeeInHtml('Fix login bug');
});
