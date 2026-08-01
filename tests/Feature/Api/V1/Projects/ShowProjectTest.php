<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create();
});

it('returns the project with its task count', function () {
    $project = Project::factory()
        ->for($this->owner)
        ->has(Task::factory()->count(4))
        ->create(['name' => 'Website Redesign']);

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('projects.show', $project))
        ->assertOk()
        ->assertJsonPath('data.id', $project->id)
        ->assertJsonPath('data.name', 'Website Redesign')
        ->assertJsonPath('data.task_count', 4);
});

it('answers another account\'s project as not found', function () {
    $project = Project::factory()->create();

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('projects.show', $project))
        ->assertNotFound()
        ->assertExactJson(['success' => false, 'message' => 'Project not found.']);
});

it('answers an id that never existed exactly as it answers another account\'s project', function () {
    $otherAccountProject = Project::factory()->create();
    $token = tokenFor($this->owner);

    $foreign = $this->withToken($token)->getJson(route('projects.show', $otherAccountProject));
    $unknown = $this->withToken($token)->getJson(route('projects.show', 999999));

    expect($unknown->getContent())->toBe($foreign->getContent())
        ->and($unknown->getStatusCode())->toBe($foreign->getStatusCode());
});

it('leaves a deleted task out of the task count', function () {
    $project = Project::factory()
        ->for($this->owner)
        ->has(Task::factory()->count(4))
        ->create();

    $project->tasks()->first()->delete();

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('projects.show', $project))
        ->assertOk()
        ->assertJsonPath('data.task_count', 3);
});

it('never exposes the owner of a project', function () {
    $project = Project::factory()->for($this->owner)->create();

    $response = $this->withToken(tokenFor($this->owner))
        ->getJson(route('projects.show', $project));

    expect($response->json('data'))->not->toHaveKey('user_id');
});
