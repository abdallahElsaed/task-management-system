<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->project = Project::factory()->for($this->owner)->create();
});

it('creates a task as todo with medium priority', function () {
    $response = $this->withToken(tokenFor($this->owner))
        ->postJson(route('tasks.store'), taskPayload($this->project->id));

    $response->assertCreated()
        ->assertJsonPath('data.title', 'Fix login bug')
        ->assertJsonPath('data.status', 'todo')
        ->assertJsonPath('data.priority', 'medium')
        ->assertJsonPath('data.due_date', '2026-08-15')
        ->assertJsonPath('data.project_id', $this->project->id);

    expect(Task::sole()->project_id)->toBe($this->project->id);
});

it('stores the priority and status that were sent', function () {
    $this->withToken(tokenFor($this->owner))
        ->postJson(route('tasks.store'), taskPayload($this->project->id, [
            'priority' => 'high',
            'status' => 'in_progress',
        ]))
        ->assertCreated()
        ->assertJsonPath('data.priority', 'high')
        ->assertJsonPath('data.status', 'in_progress');
});

it('rejects a task with no due date', function () {
    $payload = taskPayload($this->project->id);
    unset($payload['due_date']);

    $this->withToken(tokenFor($this->owner))
        ->postJson(route('tasks.store'), $payload)
        ->assertUnprocessable()
        ->assertJsonPath('errors.due_date.0', 'The due date is required.');

    expect(Task::count())->toBe(0);
});

it('accepts a due date in the past', function () {
    $this->withToken(tokenFor($this->owner))
        ->postJson(route('tasks.store'), taskPayload($this->project->id, ['due_date' => '2020-01-15']))
        ->assertCreated()
        ->assertJsonPath('data.due_date', '2020-01-15')
        ->assertJsonPath('data.is_overdue', true);
});

it('rejects a due date that is not YYYY-MM-DD', function (string $dueDate) {
    $this->withToken(tokenFor($this->owner))
        ->postJson(route('tasks.store'), taskPayload($this->project->id, ['due_date' => $dueDate]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.due_date.0', 'The due date must be a valid date in YYYY-MM-DD format.');

    expect(Task::count())->toBe(0);
})->with(['15/01/2026', '2026-13-45', 'tomorrow', '2026-08-15 09:00:00']);

it('answers a task created in another account\'s project as if that project did not exist', function () {
    $foreign = Project::factory()->create();

    $this->withToken(tokenFor($this->owner))
        ->postJson(route('tasks.store'), taskPayload($foreign->id))
        ->assertUnprocessable()
        ->assertJsonPath('errors.project_id.0', 'The selected project was not found.');

    expect(Task::count())->toBe(0);
});

it('answers a project id that never existed the same way as another account\'s', function () {
    $this->withToken(tokenFor($this->owner))
        ->postJson(route('tasks.store'), taskPayload(999999))
        ->assertUnprocessable()
        ->assertJsonPath('errors.project_id.0', 'The selected project was not found.');
});

it('answers a deleted project the same way', function () {
    $this->project->delete();

    $this->withToken(tokenFor($this->owner))
        ->postJson(route('tasks.store'), taskPayload($this->project->id))
        ->assertUnprocessable()
        ->assertJsonPath('errors.project_id.0', 'The selected project was not found.');
});

it('rejects a title shorter than three characters', function () {
    $this->withToken(tokenFor($this->owner))
        ->postJson(route('tasks.store'), taskPayload($this->project->id, ['title' => 'AB']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.title.0', 'The task title must be at least 3 characters.');

    expect(Task::count())->toBe(0);
});

it('rejects a whitespace-only title as blank', function () {
    $this->withToken(tokenFor($this->owner))
        ->postJson(route('tasks.store'), taskPayload($this->project->id, ['title' => '   ']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.title.0', 'The task title is required.');

    expect(Task::count())->toBe(0);
});

it('rejects a title longer than 255 characters', function () {
    $this->withToken(tokenFor($this->owner))
        ->postJson(route('tasks.store'), taskPayload($this->project->id, ['title' => str_repeat('a', 256)]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.title.0', 'The task title may not be longer than 255 characters.');
});

it('rejects a priority outside the three allowed values', function () {
    $this->withToken(tokenFor($this->owner))
        ->postJson(route('tasks.store'), taskPayload($this->project->id, ['priority' => 'urgent']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.priority.0', 'The priority must be one of: low, medium, high.');

    expect(Task::count())->toBe(0);
});

it('rejects a status outside the three allowed values', function () {
    $this->withToken(tokenFor($this->owner))
        ->postJson(route('tasks.store'), taskPayload($this->project->id, ['status' => 'finished']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.status.0', 'The status must be one of: todo, in_progress, done.');
});

it('accepts a priority and a status whatever their letter case', function () {
    $this->withToken(tokenFor($this->owner))
        ->postJson(route('tasks.store'), taskPayload($this->project->id, [
            'priority' => 'High',
            'status' => 'IN_PROGRESS',
        ]))
        ->assertCreated()
        ->assertJsonPath('data.priority', 'high')
        ->assertJsonPath('data.status', 'in_progress');
});

it('allows two tasks in the same project to share a title', function () {
    $token = tokenFor($this->owner);

    $this->withToken($token)->postJson(route('tasks.store'), taskPayload($this->project->id))->assertCreated();
    $this->withToken($token)->postJson(route('tasks.store'), taskPayload($this->project->id))->assertCreated();

    expect(Task::count())->toBe(2);
});

it('creates a task inside a completed or archived project', function (string $state) {
    $project = Project::factory()->for($this->owner)->{$state}()->create();

    $this->withToken(tokenFor($this->owner))
        ->postJson(route('tasks.store'), taskPayload($project->id))
        ->assertCreated();
})->with(['completed', 'archived']);

it('rejects a description longer than 2000 characters', function () {
    $this->withToken(tokenFor($this->owner))
        ->postJson(route('tasks.store'), taskPayload($this->project->id, ['description' => str_repeat('a', 2001)]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.description.0', 'The description may not be longer than 2000 characters.');
});

it('creates a task without a description', function () {
    $payload = taskPayload($this->project->id);
    unset($payload['description']);

    $this->withToken(tokenFor($this->owner))
        ->postJson(route('tasks.store'), $payload)
        ->assertCreated()
        ->assertJsonPath('data.description', null);
});

it('returns a 255-character Arabic title unchanged', function () {
    $title = str_repeat('ص', 255);

    $response = $this->withToken(tokenFor($this->owner))
        ->postJson(route('tasks.store'), taskPayload($this->project->id, ['title' => $title]));

    $response->assertCreated()->assertJsonPath('data.title', $title);

    expect($response->getContent())->toContain($title)
        ->and(Task::sole()->title)->toBe($title);
});
