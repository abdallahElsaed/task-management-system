<?php

use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create();
});

it('creates a project as active holding no tasks', function () {
    $response = $this->withToken(tokenFor($this->owner))
        ->postJson(route('projects.store'), projectPayload());

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Website Redesign')
        ->assertJsonPath('data.description', 'Q3 marketing site')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.task_count', 0);

    expect(Project::sole()->user_id)->toBe($this->owner->id);
});

it('stores the status that was sent', function () {
    $this->withToken(tokenFor($this->owner))
        ->postJson(route('projects.store'), projectPayload(['status' => 'completed']))
        ->assertCreated()
        ->assertJsonPath('data.status', 'completed');
});

it('rejects a name shorter than three characters', function () {
    $this->withToken(tokenFor($this->owner))
        ->postJson(route('projects.store'), projectPayload(['name' => 'AB']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.name.0', 'The project name must be at least 3 characters.');

    expect(Project::count())->toBe(0);
});

it('rejects a status outside the three allowed values', function () {
    $this->withToken(tokenFor($this->owner))
        ->postJson(route('projects.store'), projectPayload(['status' => 'done']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.status.0', 'The status must be one of: active, completed, archived.');

    expect(Project::count())->toBe(0);
});

it('ignores an owner id sent in the body and keeps the project with the caller', function () {
    $other = User::factory()->create();

    $this->withToken(tokenFor($this->owner))
        ->postJson(route('projects.store'), projectPayload(['user_id' => $other->id]))
        ->assertCreated();

    expect(Project::sole()->user_id)->toBe($this->owner->id);

    $this->withToken(tokenFor($other))
        ->getJson(route('projects.index'))
        ->assertOk()
        ->assertJsonPath('meta.total', 0);
});

it('allows two projects of the same account to share a name', function () {
    $token = tokenFor($this->owner);

    $this->withToken($token)->postJson(route('projects.store'), projectPayload())->assertCreated();
    $this->withToken($token)->postJson(route('projects.store'), projectPayload())->assertCreated();

    expect(Project::count())->toBe(2);
});

it('rejects a description longer than 2000 characters', function () {
    $this->withToken(tokenFor($this->owner))
        ->postJson(route('projects.store'), projectPayload(['description' => str_repeat('a', 2001)]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.description.0', 'The description may not be longer than 2000 characters.');

    expect(Project::count())->toBe(0);
});

it('accepts a description of exactly 2000 characters', function () {
    $this->withToken(tokenFor($this->owner))
        ->postJson(route('projects.store'), projectPayload(['description' => str_repeat('a', 2000)]))
        ->assertCreated();
});

it('rejects a whitespace-only name as blank', function () {
    $this->withToken(tokenFor($this->owner))
        ->postJson(route('projects.store'), projectPayload(['name' => '   ']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.name.0', 'The project name is required.');

    expect(Project::count())->toBe(0);
});

it('accepts a status whatever its letter case', function () {
    $this->withToken(tokenFor($this->owner))
        ->postJson(route('projects.store'), projectPayload(['status' => 'Active']))
        ->assertCreated()
        ->assertJsonPath('data.status', 'active');
});

it('returns a 255-character Arabic name unchanged', function () {
    $name = str_repeat('ص', 255);

    $response = $this->withToken(tokenFor($this->owner))
        ->postJson(route('projects.store'), projectPayload(['name' => $name]));

    $response->assertCreated()->assertJsonPath('data.name', $name);

    expect($response->getContent())->toContain($name)
        ->and(Project::sole()->name)->toBe($name);
});

it('rejects a name longer than 255 characters', function () {
    $this->withToken(tokenFor($this->owner))
        ->postJson(route('projects.store'), projectPayload(['name' => str_repeat('a', 256)]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.name.0', 'The project name may not be longer than 255 characters.');
});

it('creates a project without a description', function () {
    $this->withToken(tokenFor($this->owner))
        ->postJson(route('projects.store'), ['name' => 'Website Redesign'])
        ->assertCreated()
        ->assertJsonPath('data.description', null);
});
