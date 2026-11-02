<?php

use App\Enums\Projects\ProjectStatus;
use App\Enums\Tasks\TaskPriority;
use App\Enums\Tasks\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\DemoAccountSeeder;

/**
 * The dataset a reviewer lands in. Its counts are what make
 * the dashboard answer with six numbers above zero on the first call, so they are
 * asserted here rather than left to be noticed once they have drifted.
 */
beforeEach(function () {
    $this->seed(DemoAccountSeeder::class);

    $this->demo = User::where('email', DemoAccountSeeder::EMAIL)->firstOrFail();
});

it('creates the demo account with the credentials the README publishes', function () {
    $this->postJson(route('auth.login'), [
        'email' => DemoAccountSeeder::EMAIL,
        'password' => DemoAccountSeeder::PASSWORD,
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['token']]);
});

it('seeds four projects covering all three statuses', function () {
    $byStatus = Project::ownedBy($this->demo->id)
        ->get()
        ->countBy(fn (Project $project) => $project->status->value);

    expect(Project::ownedBy($this->demo->id)->count())->toBe(4);

    foreach (ProjectStatus::cases() as $status) {
        expect($byStatus->get($status->value))->toBeGreaterThan(0);
    }
});

it('seeds twenty-five tasks covering all three priorities and all three statuses', function () {
    $tasks = Task::ownedBy($this->demo->id)->get();

    expect($tasks)->toHaveCount(25);

    foreach (TaskPriority::cases() as $priority) {
        expect($tasks->where('priority', $priority))->not->toBeEmpty();
    }

    foreach (TaskStatus::cases() as $status) {
        expect($tasks->where('status', $status))->not->toBeEmpty();
    }
});

it('seeds at least three overdue tasks and at least five done ones', function () {
    expect(Task::ownedBy($this->demo->id)->overdue()->count())->toBeGreaterThanOrEqual(3)
        ->and(Task::ownedBy($this->demo->id)->withStatus(TaskStatus::Done)->count())->toBeGreaterThanOrEqual(5);
});

it('answers the first dashboard read with six counts above zero', function () {
    $counts = $this->withToken(tokenFor($this->demo))
        ->getJson(route('dashboard.show'))
        ->assertOk()
        ->json('data');

    expect($counts)->toHaveCount(6);

    foreach ($counts as $metric => $count) {
        expect($count)->toBeGreaterThan(0, "{$metric} should not be zero");
    }
});

it('returns at least one task for every priority filter', function () {
    foreach (TaskPriority::cases() as $priority) {
        $total = $this->withToken(tokenFor($this->demo))
            ->getJson(route('tasks.index', ['priority' => $priority->value]))
            ->assertOk()
            ->json('meta.total');

        expect($total)->toBeGreaterThan(0, "the {$priority->value} filter should match something");
    }
});

it('leaves the demo login working and the counts unchanged when seeded a second time', function () {
    $this->seed(DemoAccountSeeder::class);

    expect(User::where('email', DemoAccountSeeder::EMAIL)->count())->toBe(1)
        ->and(Project::ownedBy($this->demo->id)->count())->toBe(4)
        ->and(Task::ownedBy($this->demo->id)->count())->toBe(25);

    $this->postJson(route('auth.login'), [
        'email' => DemoAccountSeeder::EMAIL,
        'password' => DemoAccountSeeder::PASSWORD,
    ])->assertOk();
});
