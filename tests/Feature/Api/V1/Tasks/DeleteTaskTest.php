<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->project = Project::factory()->for($this->owner)->create();
});

it('deletes a task and drops it from the list', function () {
    $task = Task::factory()->for($this->project)->create();
    $token = tokenFor($this->owner);

    $this->withToken($token)
        ->deleteJson(route('tasks.destroy', $task))
        ->assertOk()
        ->assertExactJson([
            'success' => true,
            'message' => 'Task deleted successfully.',
            'data' => null,
        ]);

    $this->withToken($token)->getJson(route('tasks.index'))
        ->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonPath('meta.total', 0);
});

it('answers a read of a deleted task as not found', function () {
    $task = Task::factory()->for($this->project)->create();
    $token = tokenFor($this->owner);

    $this->withToken($token)->deleteJson(route('tasks.destroy', $task))->assertOk();

    $this->withToken($token)->getJson(route('tasks.show', $task))
        ->assertNotFound()
        ->assertExactJson(['success' => false, 'message' => 'Task not found.']);
});

it('answers deleting an already-deleted task as not found', function () {
    $task = Task::factory()->for($this->project)->create();
    $token = tokenFor($this->owner);

    $this->withToken($token)->deleteJson(route('tasks.destroy', $task))->assertOk();
    $this->withToken($token)->deleteJson(route('tasks.destroy', $task))->assertNotFound();
});

it('answers deleting a task under another account\'s project as not found and leaves it readable', function () {
    $foreignProject = Project::factory()->create();
    $task = Task::factory()->for($foreignProject)->create();

    $this->withToken(tokenFor($this->owner))
        ->deleteJson(route('tasks.destroy', $task))
        ->assertNotFound()
        ->assertExactJson(['success' => false, 'message' => 'Task not found.']);

    $this->withToken(tokenFor($foreignProject->user))
        ->getJson(route('tasks.show', $task))
        ->assertOk()
        ->assertJsonPath('data.id', $task->id);
});

it('returns nine of ten tasks after one is deleted', function () {
    $tasks = Task::factory()->count(10)->for($this->project)->create();
    $token = tokenFor($this->owner);

    $this->withToken($token)->deleteJson(route('tasks.destroy', $tasks->first()))->assertOk();

    $response = $this->withToken($token)->getJson(route('tasks.index'));

    $response->assertOk()->assertJsonPath('meta.total', 9);

    expect($response->json('data'))->toHaveCount(9);
});

it('keeps the deleted task in the database as a soft delete', function () {
    $task = Task::factory()->for($this->project)->create();

    $this->withToken(tokenFor($this->owner))
        ->deleteJson(route('tasks.destroy', $task))
        ->assertOk();

    expect(Task::withTrashed()->find($task->id))->not->toBeNull()
        ->and(Task::find($task->id))->toBeNull();
});

it('drops a task from every list when its parent project is deleted', function () {
    $task = Task::factory()->for($this->project)->create();
    $token = tokenFor($this->owner);

    $this->withToken($token)
        ->deleteJson(route('projects.destroy', $this->project))
        ->assertOk();

    $this->withToken($token)->getJson(route('tasks.index'))
        ->assertOk()
        ->assertJsonPath('meta.total', 0);

    $this->withToken($token)->getJson(route('tasks.show', $task))->assertNotFound();
});
