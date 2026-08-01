<?php

use App\Enums\Tasks\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->project = Project::factory()->for($this->owner)->create();
});

it('moves a task to done', function () {
    $task = Task::factory()->for($this->project)->create();
    $token = tokenFor($this->owner);

    $this->withToken($token)
        ->putJson(route('tasks.update', $task), ['status' => 'done'])
        ->assertOk()
        ->assertJsonPath('data.status', 'done');

    $this->withToken($token)
        ->getJson(route('tasks.show', $task))
        ->assertOk()
        ->assertJsonPath('data.status', 'done');
});

it('moves a done task back to todo', function () {
    $task = Task::factory()->for($this->project)->done()->create();

    $this->withToken(tokenFor($this->owner))
        ->putJson(route('tasks.update', $task), ['status' => 'todo'])
        ->assertOk()
        ->assertJsonPath('data.status', 'todo');
});

it('stops an overdue task counting as overdue once it is done, leaving its due date alone', function () {
    $task = Task::factory()->for($this->project)->create([
        'due_date' => '2026-01-01',
        'status' => TaskStatus::Todo,
    ]);

    $this->travelTo('2026-07-31');

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.show', $task))
        ->assertOk()
        ->assertJsonPath('data.is_overdue', true);

    $this->withToken(tokenFor($this->owner))
        ->putJson(route('tasks.update', $task), ['status' => 'done'])
        ->assertOk()
        ->assertJsonPath('data.is_overdue', false)
        ->assertJsonPath('data.due_date', '2026-01-01');
});

it('edits the title, priority and due date together', function () {
    $task = Task::factory()->for($this->project)->create();

    $this->withToken(tokenFor($this->owner))
        ->putJson(route('tasks.update', $task), [
            'title' => 'Rewrite the login form',
            'priority' => 'high',
            'due_date' => '2027-03-01',
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Rewrite the login form')
        ->assertJsonPath('data.priority', 'high')
        ->assertJsonPath('data.due_date', '2027-03-01');
});

it('ignores a project id sent in an update and keeps the task where it is', function () {
    $other = Project::factory()->for($this->owner)->create();
    $task = Task::factory()->for($this->project)->create();

    $this->withToken(tokenFor($this->owner))
        ->putJson(route('tasks.update', $task), [
            'title' => 'Renamed',
            'project_id' => $other->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.project_id', $this->project->id);

    expect($task->fresh()->project_id)->toBe($this->project->id);
});

it('answers an edit of a task under another account\'s project as not found', function () {
    $task = Task::factory()->create(['title' => 'Untouched']);

    $this->withToken(tokenFor($this->owner))
        ->putJson(route('tasks.update', $task), ['title' => 'Renamed'])
        ->assertNotFound()
        ->assertExactJson(['success' => false, 'message' => 'Task not found.']);

    expect($task->fresh()->title)->toBe('Untouched');
});

it('rejects a whitespace-only title and leaves the stored title alone', function () {
    $task = Task::factory()->for($this->project)->create(['title' => 'Untouched']);

    $this->withToken(tokenFor($this->owner))
        ->putJson(route('tasks.update', $task), ['title' => '   '])
        ->assertUnprocessable()
        ->assertJsonPath('errors.title.0', 'The task title is required.');

    expect($task->fresh()->title)->toBe('Untouched');
});

it('rejects a due date that is not YYYY-MM-DD on update', function () {
    $task = Task::factory()->for($this->project)->create(['due_date' => '2026-08-15']);

    $this->withToken(tokenFor($this->owner))
        ->putJson(route('tasks.update', $task), ['due_date' => '15/01/2026'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.due_date.0', 'The due date must be a valid date in YYYY-MM-DD format.');

    expect($task->fresh()->due_date->toDateString())->toBe('2026-08-15');
});

it('rejects an update that names no editable field', function () {
    $task = Task::factory()->for($this->project)->create(['title' => 'Untouched']);

    $this->withToken(tokenFor($this->owner))
        ->putJson(route('tasks.update', $task), [])
        ->assertUnprocessable()
        ->assertJsonPath(
            'errors.task.0',
            'Send at least one of: title, description, priority, status, due_date.',
        );

    expect($task->fresh()->title)->toBe('Untouched');
});

it('rejects an update carrying only a project id, which is not editable', function () {
    $other = Project::factory()->for($this->owner)->create();
    $task = Task::factory()->for($this->project)->create();

    $this->withToken(tokenFor($this->owner))
        ->putJson(route('tasks.update', $task), ['project_id' => $other->id])
        ->assertUnprocessable();

    expect($task->fresh()->project_id)->toBe($this->project->id);
});

it('rejects a status outside the three allowed values on update', function () {
    $task = Task::factory()->for($this->project)->create();

    $this->withToken(tokenFor($this->owner))
        ->putJson(route('tasks.update', $task), ['status' => 'finished'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.status.0', 'The status must be one of: todo, in_progress, done.');

    expect($task->fresh()->status)->toBe(TaskStatus::Todo);
});

it('clears a description that is sent as null', function () {
    $task = Task::factory()->for($this->project)->create(['description' => 'Something']);

    $this->withToken(tokenFor($this->owner))
        ->putJson(route('tasks.update', $task), ['description' => null])
        ->assertOk()
        ->assertJsonPath('data.description', null);
});

it('lets the later of two writes win in full', function () {
    $task = Task::factory()->for($this->project)->create();
    $token = tokenFor($this->owner);

    $this->withToken($token)->putJson(route('tasks.update', $task), ['title' => 'First write'])->assertOk();
    $this->withToken($token)->putJson(route('tasks.update', $task), ['title' => 'Second write'])->assertOk();

    expect($task->fresh()->title)->toBe('Second write');
});

it('answers an update of a deleted task as not found', function () {
    $task = Task::factory()->for($this->project)->create();
    $task->delete();

    $this->withToken(tokenFor($this->owner))
        ->putJson(route('tasks.update', $task), ['title' => 'Renamed'])
        ->assertNotFound();
});

it('accepts a patch as well as a put', function () {
    $task = Task::factory()->for($this->project)->create();

    $this->withToken(tokenFor($this->owner))
        ->patchJson(route('tasks.update', $task), ['priority' => 'low'])
        ->assertOk()
        ->assertJsonPath('data.priority', 'low');
});
