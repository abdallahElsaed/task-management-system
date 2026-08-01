<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->owner = User::factory()->create();
});

it('returns an empty page and a total of zero for an account holding no projects', function () {
    $this->withToken(tokenFor($this->owner))
        ->getJson(route('projects.index'))
        ->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonPath('meta.total', 0);
});

it('returns fifteen projects with the total and the page count on the first page', function () {
    Project::factory()->count(20)->for($this->owner)->create();

    $response = $this->withToken(tokenFor($this->owner))->getJson(route('projects.index'));

    $response->assertOk()
        ->assertJsonPath('meta.total', 20)
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonPath('meta.per_page', 15)
        ->assertJsonPath('meta.current_page', 1);

    expect($response->json('data'))->toHaveCount(15);
});

it('returns the remaining projects on the last page', function () {
    Project::factory()->count(20)->for($this->owner)->create();

    $response = $this->withToken(tokenFor($this->owner))
        ->getJson(route('projects.index', ['page' => 2]));

    $response->assertOk()->assertJsonPath('meta.current_page', 2);

    expect($response->json('data'))->toHaveCount(5);
});

it('orders the list newest first', function () {
    $token = tokenFor($this->owner);

    $this->withToken($token)->postJson(route('projects.store'), projectPayload(['name' => 'Alpha']));
    $this->withToken($token)->postJson(route('projects.store'), projectPayload(['name' => 'Beta']));

    $this->withToken($token)->getJson(route('projects.index'))
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Beta')
        ->assertJsonPath('data.1.name', 'Alpha');
});

it('never lists another account\'s projects', function () {
    Project::factory()->count(3)->create();
    Project::factory()->count(2)->for($this->owner)->create();

    $response = $this->withToken(tokenFor($this->owner))->getJson(route('projects.index'));

    $response->assertOk()->assertJsonPath('meta.total', 2);

    expect($response->json('data'))->toHaveCount(2)
        ->and(collect($response->json('data'))->pluck('id')->all())
        ->toEqualCanonicalizing($this->owner->projects()->pluck('id')->all());
});

it('accepts a hundred projects per page', function () {
    Project::factory()->count(3)->for($this->owner)->create();

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('projects.index', ['per_page' => 100]))
        ->assertOk()
        ->assertJsonPath('meta.per_page', 100);
});

it('rejects a page size outside one to a hundred', function (int|string $perPage) {
    $this->withToken(tokenFor($this->owner))
        ->getJson(route('projects.index', ['per_page' => $perPage]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.per_page.0', 'The per_page value must be between 1 and 100.');
})->with([101, 500, 0, -1, 'many']);

it('drops a deleted project from the list and from the total', function () {
    $projects = Project::factory()->count(3)->for($this->owner)->create();
    $token = tokenFor($this->owner);

    $this->withToken($token)
        ->deleteJson(route('projects.destroy', $projects->first()))
        ->assertOk();

    $response = $this->withToken($token)->getJson(route('projects.index'));

    $response->assertOk()->assertJsonPath('meta.total', 2);

    expect($response->json('data'))->toHaveCount(2);
});

it('returns an empty page beyond the last page with the correct total', function () {
    Project::factory()->count(3)->for($this->owner)->create();

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('projects.index', ['page' => 99]))
        ->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonPath('meta.total', 3);
});

it('carries the task count of every project on the page', function () {
    Project::factory()->for($this->owner)->has(Task::factory()->count(4))->create();

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('projects.index'))
        ->assertOk()
        ->assertJsonPath('data.0.task_count', 4);
});

it('reads a page of projects without querying once per project', function () {
    Project::factory()->count(20)->for($this->owner)
        ->has(Task::factory()->count(3))
        ->create();

    $token = tokenFor($this->owner);

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    $this->withToken($token)->getJson(route('projects.index'))->assertOk();

    // Token lookup, the paginator count, and the page itself — the task counts
    // ride along as a sub-select rather than one query per project.
    expect($queries)->toBeLessThan(6);
});
