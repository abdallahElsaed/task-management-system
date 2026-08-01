<?php

use App\Jobs\Notifications\SendOverdueTasksDigest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

/*
 * US-4. Delivery is the queue's problem, never a caller's, and a digest that
 * never got through leaves its tasks for tomorrow rather than losing them.
 */
beforeEach(function () {
    $this->travelTo('2026-07-31');

    Mail::fake();

    $this->owner = User::factory()->create();
    $this->project = Project::factory()->for($this->owner)->create();
});

it('hands the sending to the queue instead of doing it in the sweep', function () {
    Queue::fake();

    Task::factory()->count(2)->for($this->project)->create(['due_date' => '2026-07-30']);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    Queue::assertPushed(
        SendOverdueTasksDigest::class,
        fn (SendOverdueTasksDigest $job) => $job->ownerId === $this->owner->id
            && count($job->taskIds) === 2,
    );

    Mail::assertNothingOutgoing();
});

it('never sends or queues an email while a task is being created or edited', function () {
    $token = tokenFor($this->owner);

    $created = $this->withToken($token)
        ->postJson(route('tasks.store'), taskPayload($this->project->id, ['due_date' => '2026-07-01']))
        ->assertCreated();

    $this->withToken($token)
        ->patchJson(route('tasks.update', $created->json('data.id')), ['title' => 'Renamed'])
        ->assertOk();

    Mail::assertNothingOutgoing();
});

it('leaves the tasks unreported once every attempt at delivery has failed', function () {
    Queue::fake();

    Task::factory()->count(2)->for($this->project)->create(['due_date' => '2026-07-30']);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();
    expect(Task::unreported()->count())->toBe(0);

    failedJob()->failed(new RuntimeException('Connection refused'));

    expect(Task::unreported()->count())->toBe(2);
});

it('sweeps the same tasks again the next day and attempts a fresh email', function () {
    Queue::fake();

    Task::factory()->count(2)->for($this->project)->create(['due_date' => '2026-07-30']);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();
    failedJob()->failed(new RuntimeException('Connection refused'));

    $this->travelTo('2026-08-01');
    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    Queue::assertPushed(SendOverdueTasksDigest::class, 2);
    expect(Task::unreported()->count())->toBe(0);
});

it('records a failure the account can be identified from', function () {
    Queue::fake();
    Log::spy();

    Task::factory()->count(2)->for($this->project)->create(['due_date' => '2026-07-30']);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();
    failedJob()->failed(new RuntimeException('Connection refused'));

    Log::shouldHaveReceived('error')->withArgs(
        fn (string $message, array $context): bool => $context['owner_id'] === $this->owner->id
            && $context['task_count'] === 2
            && $context['exception'] === 'Connection refused',
    );
});

/**
 * The digest the sweep queued, as the queue hands it back when the last attempt
 * has gone.
 */
function failedJob(): SendOverdueTasksDigest
{
    return Queue::pushed(SendOverdueTasksDigest::class)->sole();
}
