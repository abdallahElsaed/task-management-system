<?php

use App\Jobs\Notifications\SendOverdueTasksDigest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\Notifications\OverdueNotificationService;
use Illuminate\Contracts\Process\InvokedProcess;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

/*
 * `withoutOverlapping` only keeps scheduled runs apart: a sweep started by hand
 * walks straight past it. So the race is run with the real command in separate
 * processes, on real row locks, against the real `database` queue.
 *
 * The test holds the row locks itself and lets go only once every sweep is stuck
 * waiting for them. Each one has decided by then which accounts to visit, so all
 * of them go after the same tasks at the same moment.
 */
const CONCURRENT_SWEEPS = 2;

const SECONDS_TO_REACH_THE_LOCK = 15;

const SECONDS_TO_FINISH = 30;

beforeEach(function () {
    $database = DB::connection()->getDatabaseName();

    throw_unless(
        DB::connection()->getDriverName() === 'mysql' && str_ends_with($database, '_test'),
        RuntimeException::class,
        "Refusing to wipe [{$database}]: the race only runs on a MySQL database ending in _test.",
    );

    $this->artisan('migrate:fresh')->assertSuccessful();
});

it('queues each task exactly once however many sweeps race for it', function () {
    $owners = User::factory()->count(3)->create();

    $owners->each(fn (User $owner) => createOverdueTasksFor($owner));

    $taskIds = Task::pluck('id')->all();

    DB::beginTransaction();
    Task::whereKey($taskIds)->lockForUpdate()->get();

    $sweeps = collect(range(1, CONCURRENT_SWEEPS))->map(fn () => startSweep());

    waitUntilBlocked($sweeps);

    DB::commit();

    $sweeps->each(fn (InvokedProcess $sweep) => expect($sweep->wait()->exitCode())->toBe(0));

    $digests = queuedDigests();

    expect($digests->pluck('ownerId')->sort()->values()->all())->toBe($owners->modelKeys());
    expect($digests->pluck('taskIds')->flatten()->sort()->values()->all())->toBe($taskIds);
    expect(Task::unreported()->count())->toBe(0);
});

/*
 * The race above only deadlocks now and then, so the retry is proved on its own:
 * the first locking read fails the way MySQL fails the victim of a deadlock.
 */
it('runs the claim again when the database abandons it to break a deadlock', function () {
    createOverdueTasksFor(User::factory()->create());

    $deadlocked = false;

    DB::beforeExecuting(function (string $query) use (&$deadlocked): void {
        if (! $deadlocked && str_contains($query, 'for update')) {
            $deadlocked = true;

            throw new PDOException('Deadlock found when trying to get lock; try restarting transaction');
        }
    });

    expect(app(OverdueNotificationService::class)->sweep())->toBe(1);

    expect($deadlocked)->toBeTrue();
    expect(queuedDigests())->toHaveCount(1);
    expect(Task::unreported()->count())->toBe(0);
});

function createOverdueTasksFor(User $owner): void
{
    Task::factory()
        ->count(2)
        ->for(Project::factory()->for($owner))
        ->create(['due_date' => today()->subDay()]);
}

/**
 * The real command in its own process. The connection is spelled out rather than
 * inherited so that the sweep can never fall back to the database named in `.env`.
 */
function startSweep(): InvokedProcess
{
    $connection = config('database.connections.mysql');

    return Process::path(base_path())
        ->timeout(SECONDS_TO_FINISH)
        ->env([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $connection['host'],
            'DB_PORT' => $connection['port'],
            'DB_DATABASE' => $connection['database'],
            'DB_USERNAME' => $connection['username'],
            'DB_PASSWORD' => $connection['password'],
            'DB_URL' => '',
            'QUEUE_CONNECTION' => 'database',
        ])
        ->start([PHP_BINARY, 'artisan', 'tasks:notify-overdue']);
}

/**
 * Proof that the sweeps really overlap, rather than one finishing before the next
 * begins. The account running the suite needs to be able to read `performance_schema`.
 *
 * @param  Collection<int, InvokedProcess>  $sweeps
 */
function waitUntilBlocked(Collection $sweeps): void
{
    $deadline = microtime(true) + SECONDS_TO_REACH_THE_LOCK;

    while (blockedTransactions() < $sweeps->count()) {
        $stopped = $sweeps->first(fn (InvokedProcess $sweep) => ! $sweep->running());

        throw_if(
            $stopped !== null,
            RuntimeException::class,
            'A sweep stopped before reaching the row locks: '.$stopped?->errorOutput(),
        );

        throw_if(
            microtime(true) > $deadline,
            RuntimeException::class,
            'The sweeps never reached the row locks.',
        );

        usleep(50_000);
    }
}

function blockedTransactions(): int
{
    return DB::table('performance_schema.data_lock_waits')
        ->distinct()
        ->count('REQUESTING_ENGINE_TRANSACTION_ID');
}

/**
 * @return Collection<int, SendOverdueTasksDigest>
 */
function queuedDigests(): Collection
{
    return DB::table('jobs')->pluck('payload')->map(
        fn (string $payload): SendOverdueTasksDigest => unserialize(
            json_decode($payload, true)['data']['command'],
        ),
    );
}
