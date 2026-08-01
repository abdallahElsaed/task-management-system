<?php

use App\Enums\Projects\ProjectStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create();
});

it('moves a project to archived', function () {
    $project = Project::factory()->for($this->owner)->create();
    $token = tokenFor($this->owner);

    $this->withToken($token)
        ->putJson(route('projects.update', $project), ['status' => 'archived'])
        ->assertOk()
        ->assertJsonPath('data.status', 'archived');

    $this->withToken($token)
        ->getJson(route('projects.show', $project))
        ->assertOk()
        ->assertJsonPath('data.status', 'archived');
});

it('moves an archived project back to active', function () {
    $project = Project::factory()->for($this->owner)->archived()->create();

    $this->withToken(tokenFor($this->owner))
        ->putJson(route('projects.update', $project), ['status' => 'active'])
        ->assertOk()
        ->assertJsonPath('data.status', 'active');
});

it('renames a project', function () {
    $project = Project::factory()->for($this->owner)->create(['name' => 'Old name']);

    $this->withToken(tokenFor($this->owner))
        ->putJson(route('projects.update', $project), ['name' => 'New name'])
        ->assertOk()
        ->assertJsonPath('data.name', 'New name');

    expect($project->fresh()->name)->toBe('New name');
});

it('answers a rename of another account\'s project as not found and leaves its name alone', function () {
    $project = Project::factory()->create(['name' => 'Untouched']);

    $this->withToken(tokenFor($this->owner))
        ->putJson(route('projects.update', $project), ['name' => 'Renamed'])
        ->assertNotFound()
        ->assertExactJson(['success' => false, 'message' => 'Project not found.']);

    expect($project->fresh()->name)->toBe('Untouched');
});

it('rejects a whitespace-only name and leaves the stored name alone', function () {
    $project = Project::factory()->for($this->owner)->create(['name' => 'Untouched']);

    $this->withToken(tokenFor($this->owner))
        ->putJson(route('projects.update', $project), ['name' => '   '])
        ->assertUnprocessable()
        ->assertJsonPath('errors.name.0', 'The project name is required.');

    expect($project->fresh()->name)->toBe('Untouched');
});

it('rejects an update that names no editable field', function () {
    $project = Project::factory()->for($this->owner)->create(['name' => 'Untouched']);

    $this->withToken(tokenFor($this->owner))
        ->putJson(route('projects.update', $project), [])
        ->assertUnprocessable()
        ->assertJsonPath('errors.project.0', 'Send at least one of: name, description, status.');

    expect($project->fresh()->name)->toBe('Untouched');
});

it('ignores an owner id sent in an update body', function () {
    $other = User::factory()->create();
    $project = Project::factory()->for($this->owner)->create();

    $this->withToken(tokenFor($this->owner))
        ->putJson(route('projects.update', $project), [
            'name' => 'Renamed',
            'user_id' => $other->id,
        ])
        ->assertOk();

    expect($project->fresh()->user_id)->toBe($this->owner->id);
});

it('rejects a status outside the three allowed values on update', function () {
    $project = Project::factory()->for($this->owner)->create();

    $this->withToken(tokenFor($this->owner))
        ->putJson(route('projects.update', $project), ['status' => 'done'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.status.0', 'The status must be one of: active, completed, archived.');

    expect($project->fresh()->status)->toBe(ProjectStatus::Active);
});

it('clears a description that is sent as null', function () {
    $project = Project::factory()->for($this->owner)->create(['description' => 'Something']);

    $this->withToken(tokenFor($this->owner))
        ->putJson(route('projects.update', $project), ['description' => null])
        ->assertOk()
        ->assertJsonPath('data.description', null);
});

it('keeps the task count on the updated project', function () {
    $project = Project::factory()->for($this->owner)
        ->has(Task::factory()->count(2))
        ->create();

    $this->withToken(tokenFor($this->owner))
        ->putJson(route('projects.update', $project), ['name' => 'Renamed'])
        ->assertOk()
        ->assertJsonPath('data.task_count', 2);
});

it('answers an update of a deleted project as not found', function () {
    $project = Project::factory()->for($this->owner)->create();
    $project->delete();

    $this->withToken(tokenFor($this->owner))
        ->putJson(route('projects.update', $project), ['name' => 'Renamed'])
        ->assertNotFound();
});
