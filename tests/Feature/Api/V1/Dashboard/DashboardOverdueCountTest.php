<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

/*
 * Every date below is written the way FT-004's acceptance criteria write them,
 * so the clock is frozen on their "today" rather than the day the suite runs.
 */
beforeEach(function () {
    $this->travelTo('2026-07-31');

    $this->owner = User::factory()->create();
    $this->project = Project::factory()->for($this->owner)->create();
});

it('counts a task due yesterday and not done as overdue', function () {
    Task::factory()->for($this->project)->create(['due_date' => '2026-07-30']);

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('dashboard.show'))
        ->assertOk()
        ->assertJsonPath('data.overdue_tasks', 1);
});

it('never counts a task due today as overdue', function () {
    Task::factory()->for($this->project)->create(['due_date' => '2026-07-31']);

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('dashboard.show'))
        ->assertOk()
        ->assertJsonPath('data.overdue_tasks', 0)
        ->assertJsonPath('data.pending_tasks', 1);
});

it('never counts a done task as overdue however old its due date', function () {
    Task::factory()->for($this->project)->done()->create(['due_date' => '2026-07-30']);

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('dashboard.show'))
        ->assertOk()
        ->assertJsonPath('data.overdue_tasks', 0)
        ->assertJsonPath('data.completed_tasks', 1)
        ->assertJsonPath('data.pending_tasks', 0);
});

it('counts one late task in the total, the pending count and the overdue count at once', function () {
    Task::factory()->for($this->project)->inProgress()->create(['due_date' => '2026-07-01']);

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('dashboard.show'))
        ->assertOk()
        ->assertJsonPath('data.total_tasks', 1)
        ->assertJsonPath('data.pending_tasks', 1)
        ->assertJsonPath('data.overdue_tasks', 1)
        ->assertJsonPath('data.completed_tasks', 0);
});

it('keeps the overdue tasks inside the pending count rather than beside it', function () {
    Task::factory()->count(3)->for($this->project)->create(['due_date' => '2026-07-01']);
    Task::factory()->count(5)->for($this->project)->create(['due_date' => '2026-08-30']);
    Task::factory()->count(2)->for($this->project)->done()->create(['due_date' => '2026-08-30']);

    $counts = $this->withToken(tokenFor($this->owner))
        ->getJson(route('dashboard.show'))
        ->assertOk()
        ->assertJsonPath('data.total_tasks', 10)
        ->assertJsonPath('data.completed_tasks', 2)
        ->assertJsonPath('data.pending_tasks', 8)
        ->assertJsonPath('data.overdue_tasks', 3)
        ->json('data');

    expect($counts['overdue_tasks'])->toBeLessThanOrEqual($counts['pending_tasks']);
});

it('moves a task out of the overdue and pending counts the moment it is done', function () {
    $tasks = Task::factory()->count(3)->for($this->project)->create(['due_date' => '2026-07-01']);
    $token = tokenFor($this->owner);

    $this->withToken($token)
        ->getJson(route('dashboard.show'))
        ->assertOk()
        ->assertJsonPath('data.overdue_tasks', 3)
        ->assertJsonPath('data.pending_tasks', 3)
        ->assertJsonPath('data.completed_tasks', 0);

    $this->withToken($token)
        ->patchJson(route('tasks.update', $tasks->first()), ['status' => 'done'])
        ->assertOk();

    $this->withToken($token)
        ->getJson(route('dashboard.show'))
        ->assertOk()
        ->assertJsonPath('data.overdue_tasks', 2)
        ->assertJsonPath('data.pending_tasks', 2)
        ->assertJsonPath('data.completed_tasks', 1);
});

it('starts counting yesterday\'s tasks as overdue one minute after midnight', function () {
    $this->travelTo('2026-07-31 23:59:00');

    Task::factory()->for($this->project)->create(['due_date' => '2026-07-31']);

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('dashboard.show'))
        ->assertOk()
        ->assertJsonPath('data.overdue_tasks', 0);

    $this->travelTo('2026-08-01 00:01:00');

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('dashboard.show'))
        ->assertOk()
        ->assertJsonPath('data.overdue_tasks', 1);
});

it('never counts another account\'s overdue tasks', function () {
    Task::factory()->count(4)->create(['due_date' => '2026-07-01']);
    Task::factory()->for($this->project)->create(['due_date' => '2026-07-01']);

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('dashboard.show'))
        ->assertOk()
        ->assertJsonPath('data.overdue_tasks', 1);
});

it('never counts a deleted overdue task', function () {
    $tasks = Task::factory()->count(3)->for($this->project)->create(['due_date' => '2026-07-01']);
    $token = tokenFor($this->owner);

    $tasks->take(2)->each(function (Task $task) use ($token): void {
        $this->withToken($token)->deleteJson(route('tasks.destroy', $task))->assertOk();
    });

    $this->withToken($token)
        ->getJson(route('dashboard.show'))
        ->assertOk()
        ->assertJsonPath('data.overdue_tasks', 1);
});
