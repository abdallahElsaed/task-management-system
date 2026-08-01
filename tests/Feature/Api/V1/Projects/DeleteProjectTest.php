<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create();
});

it('deletes a project and confirms it', function () {
    $project = Project::factory()->for($this->owner)->create();
    $token = tokenFor($this->owner);

    $this->withToken($token)
        ->deleteJson(route('projects.destroy', $project))
        ->assertOk()
        ->assertExactJson([
            'success' => true,
            'message' => 'Project deleted successfully.',
            'data' => null,
        ]);

    $this->withToken($token)
        ->getJson(route('projects.index'))
        ->assertOk()
        ->assertJsonPath('meta.total', 0);
});

it('takes every task in the project with it', function () {
    $project = Project::factory()->for($this->owner)
        ->has(Task::factory()->count(12))
        ->create();

    $this->withToken(tokenFor($this->owner))
        ->deleteJson(route('projects.destroy', $project))
        ->assertOk();

    expect(Task::count())->toBe(0)
        ->and(Task::withTrashed()->count())->toBe(12)
        ->and(Project::withTrashed()->sole()->trashed())->toBeTrue();
});

it('leaves another account\'s tasks untouched', function () {
    $mine = Project::factory()->for($this->owner)->has(Task::factory()->count(2))->create();
    Project::factory()->has(Task::factory()->count(3))->create();

    $this->withToken(tokenFor($this->owner))
        ->deleteJson(route('projects.destroy', $mine))
        ->assertOk();

    expect(Task::count())->toBe(3);
});

it('answers a read of a deleted project as not found', function () {
    $project = Project::factory()->for($this->owner)->create();
    $token = tokenFor($this->owner);

    $this->withToken($token)->deleteJson(route('projects.destroy', $project))->assertOk();

    $this->withToken($token)
        ->getJson(route('projects.show', $project))
        ->assertNotFound()
        ->assertExactJson(['success' => false, 'message' => 'Project not found.']);
});

it('answers a second delete of the same project as not found', function () {
    $project = Project::factory()->for($this->owner)->create();
    $token = tokenFor($this->owner);

    $this->withToken($token)->deleteJson(route('projects.destroy', $project))->assertOk();

    $this->withToken($token)
        ->deleteJson(route('projects.destroy', $project))
        ->assertNotFound()
        ->assertExactJson(['success' => false, 'message' => 'Project not found.']);
});

it('answers a delete of another account\'s project as not found and leaves it readable by its owner', function () {
    $project = Project::factory()->create();

    $this->withToken(tokenFor($this->owner))
        ->deleteJson(route('projects.destroy', $project))
        ->assertNotFound()
        ->assertExactJson(['success' => false, 'message' => 'Project not found.']);

    $this->withToken(tokenFor($project->user))
        ->getJson(route('projects.show', $project))
        ->assertOk()
        ->assertJsonPath('data.id', $project->id);
});
