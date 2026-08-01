<?php

use App\Enums\Tasks\TaskPriority;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->project = Project::factory()->for($this->owner)->create();
});

it('returns only the tasks carrying the requested status', function () {
    Task::factory()->count(4)->for($this->project)->create();
    Task::factory()->count(6)->for($this->project)->done()->create();

    $response = $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['status' => 'todo']));

    $response->assertOk()->assertJsonPath('meta.total', 4);

    expect(collect($response->json('data'))->pluck('status')->unique()->all())->toBe(['todo']);
});

it('returns only the tasks carrying the requested priority', function () {
    Task::factory()->count(3)->for($this->project)->create(['priority' => TaskPriority::High]);
    Task::factory()->count(5)->for($this->project)->create();

    $response = $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['priority' => 'high']));

    $response->assertOk()->assertJsonPath('meta.total', 3);

    expect(collect($response->json('data'))->pluck('priority')->unique()->all())->toBe(['high']);
});

it('combines status and priority with AND', function () {
    Task::factory()->count(2)->for($this->project)->create(['priority' => TaskPriority::High]);
    Task::factory()->count(3)->for($this->project)->done()->create(['priority' => TaskPriority::High]);
    Task::factory()->count(5)->for($this->project)->create();

    $response = $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['status' => 'todo', 'priority' => 'high']));

    $response->assertOk()->assertJsonPath('meta.total', 2);

    expect($response->json('data'))->toHaveCount(2);
});

it('returns only one project\'s tasks when a project is named', function () {
    $second = Project::factory()->for($this->owner)->create();

    Task::factory()->count(4)->for($this->project)->create();
    Task::factory()->count(3)->for($second)->create();

    $response = $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['project_id' => $this->project->id]));

    $response->assertOk()->assertJsonPath('meta.total', 4);

    expect(collect($response->json('data'))->pluck('project_id')->unique()->all())
        ->toBe([$this->project->id]);
});

it('rejects a project filter naming another account\'s project and returns no page', function () {
    $foreign = Project::factory()->create();
    Task::factory()->count(3)->for($this->project)->create();

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['project_id' => $foreign->id]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.project_id.0', 'The selected project was not found.')
        ->assertJsonMissingPath('data');
});

it('rejects a status filter outside the allowed set and returns no page', function () {
    Task::factory()->count(3)->for($this->project)->create();

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['status' => 'finished']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.status.0', 'The status must be one of: todo, in_progress, done.')
        ->assertJsonMissingPath('data');
});

it('rejects a priority filter outside the allowed set and returns no page', function () {
    Task::factory()->count(3)->for($this->project)->create();

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['priority' => 'urgent']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.priority.0', 'The priority must be one of: low, medium, high.')
        ->assertJsonMissingPath('data');
});

it('tells the caller nothing matched when active filters return an empty page', function () {
    Task::factory()->count(10)->for($this->project)->create(['priority' => TaskPriority::High]);

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['status' => 'done', 'priority' => 'low']))
        ->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonPath('meta.total', 0)
        ->assertJsonPath('message', 'No tasks match these filters.');
});

it('still says "no tasks yet" when filters are active but the account holds nothing', function () {
    $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['status' => 'done']))
        ->assertOk()
        ->assertJsonPath('meta.total', 0)
        ->assertJsonPath('message', 'You have no tasks yet.');
});

it('accepts a filter value whatever its letter case', function () {
    Task::factory()->count(2)->for($this->project)->done()->create();

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['status' => 'DONE']))
        ->assertOk()
        ->assertJsonPath('meta.total', 2);
});

it('treats a filter sent with no value as no filter at all', function () {
    Task::factory()->count(3)->for($this->project)->create();

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['status' => '', 'priority' => '', 'project_id' => '']))
        ->assertOk()
        ->assertJsonPath('meta.total', 3);
});

it('combines all four filters at once', function () {
    $second = Project::factory()->for($this->owner)->create();

    Task::factory()->for($this->project)->create([
        'title' => 'Fix login bug',
        'priority' => TaskPriority::High,
    ]);
    Task::factory()->for($this->project)->done()->create([
        'title' => 'Fix login form',
        'priority' => TaskPriority::High,
    ]);
    Task::factory()->for($second)->create([
        'title' => 'Fix login redirect',
        'priority' => TaskPriority::High,
    ]);
    Task::factory()->for($this->project)->create(['title' => 'Update profile']);

    $response = $this->withToken(tokenFor($this->owner))->getJson(route('tasks.index', [
        'status' => 'todo',
        'priority' => 'high',
        'project_id' => $this->project->id,
        'search' => 'login',
    ]));

    $response->assertOk()->assertJsonPath('meta.total', 1);

    expect($response->json('data.0.title'))->toBe('Fix login bug');
});

it('never returns another account\'s tasks through a filter', function () {
    Task::factory()->count(8)->create();
    Task::factory()->count(2)->for($this->project)->done()->create();

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['status' => 'done']))
        ->assertOk()
        ->assertJsonPath('meta.total', 2);
});
