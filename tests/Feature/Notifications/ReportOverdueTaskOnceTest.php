<?php

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Enums\Tasks\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/*
 * The rule this file exists for: a task is named in exactly one email,
 * ever. Everything below is a way for a task to escape that rule, and none of
 * them may work.
 */
beforeEach(function () {
    $this->travelTo('2026-07-31');

    Mail::fake();

    $this->owner = User::factory()->create();
    $this->project = Project::factory()->for($this->owner)->create();
});

it('never names a task that yesterday\'s email already named', function () {
    Task::factory()->for($this->project)->create(['due_date' => '2026-07-29']);

    $this->travelTo('2026-07-30');
    $this->artisan('tasks:notify-overdue')->assertSuccessful();
    Mail::assertSentCount(1);

    $this->travelTo('2026-07-31');
    $this->artisan('tasks:notify-overdue')->assertSuccessful();
    Mail::assertSentCount(1);
});

it('sends one email about a task that stays overdue for thirty days, not thirty', function () {
    Task::factory()->for($this->project)->create(['due_date' => '2026-07-30']);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    foreach (range(2, 30) as $ignored) {
        $this->travel(1)->days();
        $this->artisan('tasks:notify-overdue')->assertSuccessful();
    }

    Mail::assertSentCount(1);
});

it('sends nothing about a task finished before the sweep ran', function () {
    $task = Task::factory()->for($this->project)->create(['due_date' => '2026-07-30']);
    $task->update(['status' => TaskStatus::Done]);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    Mail::assertNothingOutgoing();
});

it('sends nothing about a task re-dated into the future before the sweep ran', function () {
    $task = Task::factory()->for($this->project)->create(['due_date' => '2026-07-30']);
    $task->update(['due_date' => '2026-12-01']);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    Mail::assertNothingOutgoing();
});

it('keeps a reported task reported when it is moved back to todo', function () {
    $task = Task::factory()->for($this->project)->create(['due_date' => '2026-07-30']);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();
    Mail::assertSentCount(1);

    $task->update(['status' => TaskStatus::Todo]);

    $this->travelTo('2026-08-01');
    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    Mail::assertSentCount(1);
});

it('sends nothing the second time the sweep is run on the same day', function () {
    Task::factory()->count(3)->for($this->project)->create(['due_date' => '2026-07-30']);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();
    Mail::assertSentCount(1);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();
    Mail::assertSentCount(1);
});

it('sends nothing about an overdue task whose project has been deleted', function () {
    Task::factory()->for($this->project)->create(['due_date' => '2026-07-30']);

    $this->project->delete();

    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    Mail::assertNothingOutgoing();
});

/*
 * This is the first sweep after the backfill below, which is meant for switching
 * the feature on over existing data. No migration calls it yet.
 */
it('sends nobody a backlog when the feature is switched on over overdue work', function () {
    Task::factory()->count(200)->for($this->project)->create(['due_date' => '2026-07-01']);

    app(TaskRepositoryInterface::class)->markAllOverdueAsReported();

    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    Mail::assertNothingOutgoing();
    expect(Task::unreported()->count())->toBe(0);
});
