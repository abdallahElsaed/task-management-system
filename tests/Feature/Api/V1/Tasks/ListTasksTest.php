<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->project = Project::factory()->for($this->owner)->create();
});

it('tells an account holding no tasks that it has none yet', function () {
    $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index'))
        ->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonPath('meta.total', 0)
        ->assertJsonPath('message', 'You have no tasks yet.');
});

it('carries no empty-state message on a page that holds results', function () {
    Task::factory()->count(3)->for($this->project)->create();

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index'))
        ->assertOk()
        ->assertJsonMissingPath('message');
});

it('returns fifteen tasks with the total and the page count on the first page', function () {
    Task::factory()->count(20)->for($this->project)->create();

    $response = $this->withToken(tokenFor($this->owner))->getJson(route('tasks.index'));

    $response->assertOk()
        ->assertJsonPath('meta.total', 20)
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonPath('meta.per_page', 15)
        ->assertJsonPath('meta.current_page', 1);

    expect($response->json('data'))->toHaveCount(15);
});

it('returns the remaining tasks on the last page', function () {
    Task::factory()->count(20)->for($this->project)->create();

    $response = $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['page' => 2]));

    $response->assertOk()->assertJsonPath('meta.current_page', 2);

    expect($response->json('data'))->toHaveCount(5);
});

it('orders the list by due date, soonest first', function () {
    foreach (['2026-09-01', '2026-01-10', '2026-08-15'] as $dueDate) {
        Task::factory()->for($this->project)->create(['due_date' => $dueDate]);
    }

    $response = $this->withToken(tokenFor($this->owner))->getJson(route('tasks.index'));

    $response->assertOk();

    expect(collect($response->json('data'))->pluck('due_date')->all())
        ->toBe(['2026-01-10', '2026-08-15', '2026-09-01']);
});

it('puts the more recently created of two tasks sharing a due date first', function () {
    $token = tokenFor($this->owner);

    $this->withToken($token)
        ->postJson(route('tasks.store'), taskPayload($this->project->id, [
            'title' => 'Older',
            'due_date' => '2026-08-15',
        ]))->assertCreated();

    $this->travel(1)->minutes();

    $this->withToken($token)
        ->postJson(route('tasks.store'), taskPayload($this->project->id, [
            'title' => 'Newer',
            'due_date' => '2026-08-15',
        ]))->assertCreated();

    $this->withToken($token)->getJson(route('tasks.index'))
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Newer')
        ->assertJsonPath('data.1.title', 'Older');
});

it('returns tasks from every project the caller owns in one page', function () {
    $second = Project::factory()->for($this->owner)->create();

    Task::factory()->count(2)->for($this->project)->create();
    Task::factory()->count(3)->for($second)->create();

    $response = $this->withToken(tokenFor($this->owner))->getJson(route('tasks.index'));

    $response->assertOk()->assertJsonPath('meta.total', 5);

    expect(collect($response->json('data'))->pluck('project_id')->unique()->sort()->values()->all())
        ->toBe([$this->project->id, $second->id]);
});

it('never lists a task under another account\'s project', function () {
    Task::factory()->count(12)->create();
    Task::factory()->count(3)->for($this->project)->create();

    $response = $this->withToken(tokenFor($this->owner))->getJson(route('tasks.index'));

    $response->assertOk()->assertJsonPath('meta.total', 3);

    expect($response->json('data'))->toHaveCount(3);
});

it('accepts a hundred tasks per page', function () {
    Task::factory()->count(3)->for($this->project)->create();

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['per_page' => 100]))
        ->assertOk()
        ->assertJsonPath('meta.per_page', 100);
});

it('rejects a page size outside one to a hundred', function (int|string $perPage) {
    $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['per_page' => $perPage]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.per_page.0', 'The per_page value must be between 1 and 100.');
})->with([101, 500, 0, -1, 'many']);

it('returns an empty page beyond the last page with the correct total and no empty-state message', function () {
    Task::factory()->count(3)->for($this->project)->create();

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['page' => 99]))
        ->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonPath('meta.total', 3)
        ->assertJsonMissingPath('message');
});

it('reads a page of tasks without querying once per task', function () {
    Task::factory()->count(20)->for($this->project)->create();

    $token = tokenFor($this->owner);

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    $this->withToken($token)->getJson(route('tasks.index'))->assertOk();

    // Token lookup, the paginator count, and the page itself — the owner check
    // rides along as a sub-query rather than one query per task.
    expect($queries)->toBeLessThan(6);
});
