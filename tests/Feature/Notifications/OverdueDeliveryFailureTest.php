<?php

use App\Jobs\Notifications\SendOverdueTasksDigest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

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

/*
 * A queue that cannot take the job must not cost the email. The `jobs` table
 * disappearing is a real failure on the real driver, and `after_commit` covers a
 * connection configured to hold jobs back until the transaction has committed.
 */
it('keeps the tasks for the next sweep when the queue refuses the digest', function (bool $heldUntilCommit) {
    config([
        'queue.default' => 'database',
        'queue.connections.database.after_commit' => $heldUntilCommit,
    ]);

    Task::factory()->count(2)->for($this->project)->create(['due_date' => '2026-07-30']);

    Schema::rename('jobs', 'jobs_offline');

    expect(fn () => $this->artisan('tasks:notify-overdue')->run())->toThrow(QueryException::class);
    expect(Task::unreported()->count())->toBe(2);

    Schema::rename('jobs_offline', 'jobs');

    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    expect(DB::table('jobs')->count())->toBe(1);
    expect(Task::unreported()->count())->toBe(0);
})->with([
    'pushed straight away' => false,
    'held until commit' => true,
]);

it('leaves the tasks unreported when the sync queue fails to send inline', function () {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('Connection refused'));

    Task::factory()->count(2)->for($this->project)->create(['due_date' => '2026-07-30']);

    expect(fn () => $this->artisan('tasks:notify-overdue')->run())
        ->toThrow(RuntimeException::class, 'Connection refused');

    expect(Task::unreported()->count())->toBe(2);
});

/**
 * The digest the sweep queued, as the queue hands it back when the last attempt
 * has gone.
 */
function failedJob(): SendOverdueTasksDigest
{
    return Queue::pushed(SendOverdueTasksDigest::class)->sole();
}
