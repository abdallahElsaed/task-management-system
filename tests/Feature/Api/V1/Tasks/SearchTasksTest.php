<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->project = Project::factory()->for($this->owner)->create();
});

/**
 * @param  array<int, string>  $titles
 */
function tasksTitled(Project $project, array $titles): void
{
    foreach ($titles as $title) {
        Task::factory()->for($project)->create(['title' => $title]);
    }
}

it('matches any part of the title, ignoring letter case', function () {
    tasksTitled($this->project, ['Fix login bug', 'LOGIN form', 'Update profile']);

    $response = $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['search' => 'login']));

    $response->assertOk()->assertJsonPath('meta.total', 2);

    expect(collect($response->json('data'))->pluck('title')->all())
        ->toEqualCanonicalizing(['Fix login bug', 'LOGIN form']);
});

it('reads the title only and never the description', function () {
    Task::factory()->for($this->project)->create([
        'title' => 'Fix login bug',
        'description' => 'Unrelated',
    ]);
    Task::factory()->for($this->project)->create([
        'title' => 'Homepage',
        'description' => 'the login is broken',
    ]);

    $response = $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['search' => 'login']));

    $response->assertOk()->assertJsonPath('meta.total', 1);

    expect($response->json('data.0.title'))->toBe('Fix login bug');
});

it('matches inside an Arabic title', function () {
    tasksTitled($this->project, ['تصميم الصفحة الرئيسية', 'Update profile']);

    $response = $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['search' => 'الصفحة']));

    $response->assertOk()->assertJsonPath('meta.total', 1);

    expect($response->json('data.0.title'))->toBe('تصميم الصفحة الرئيسية');
});

it('drops leading and trailing spaces from the search term', function () {
    tasksTitled($this->project, ['Fix login bug', 'LOGIN form', 'Update profile']);
    $token = tokenFor($this->owner);

    $padded = $this->withToken($token)->getJson(route('tasks.index', ['search' => '  login  ']));
    $plain = $this->withToken($token)->getJson(route('tasks.index', ['search' => 'login']));

    $padded->assertOk();

    expect($padded->json())->toBe($plain->json());
});

it('treats an empty or whitespace-only search term as no search term', function (string $term) {
    tasksTitled($this->project, ['Fix login bug', 'Update profile']);
    $token = tokenFor($this->owner);

    $searched = $this->withToken($token)->getJson(route('tasks.index', ['search' => $term]));
    $unfiltered = $this->withToken($token)->getJson(route('tasks.index'));

    $searched->assertOk()->assertJsonPath('meta.total', 2);

    expect($searched->json())->toBe($unfiltered->json());
})->with(['', '   ']);

it('combines the search with a status filter', function () {
    tasksTitled($this->project, ['Fix login bug', 'LOGIN form', 'Reset password', 'Update profile']);
    Task::factory()->count(2)->for($this->project)->done()->create(['title' => 'Old login screen']);

    $response = $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['search' => 'login', 'status' => 'todo']));

    $response->assertOk()->assertJsonPath('meta.total', 2);

    expect(collect($response->json('data'))->pluck('title')->all())
        ->toEqualCanonicalizing(['Fix login bug', 'LOGIN form']);
});

it('tells the caller nothing matched when the search finds no task', function () {
    tasksTitled($this->project, ['Fix login bug', 'Update profile']);

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['search' => 'zzzzz']))
        ->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonPath('meta.total', 0)
        ->assertJsonPath('message', 'No tasks match these filters.');
});

it('never matches a task under another account\'s project', function () {
    $foreign = Project::factory()->create();
    tasksTitled($foreign, ['Fix login bug', 'LOGIN form']);
    tasksTitled($this->project, ['Fix login redirect']);

    $response = $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['search' => 'login']));

    $response->assertOk()->assertJsonPath('meta.total', 1);

    expect($response->json('data.0.title'))->toBe('Fix login redirect');
});

it('rejects a search term longer than 255 characters', function () {
    $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['search' => str_repeat('a', 256)]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.search.0', 'The search term may not be longer than 255 characters.');
});

it('accepts a search term of exactly 255 characters', function () {
    $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['search' => str_repeat('a', 255)]))
        ->assertOk();
});

it('keeps the due-date order within a search result', function () {
    foreach ([['Fix login bug', '2026-09-01'], ['Login form', '2026-01-10'], ['Old login', '2026-08-15']] as [$title, $dueDate]) {
        Task::factory()->for($this->project)->create(['title' => $title, 'due_date' => $dueDate]);
    }

    $response = $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.index', ['search' => 'login']));

    $response->assertOk();

    expect(collect($response->json('data'))->pluck('due_date')->all())
        ->toBe(['2026-01-10', '2026-08-15', '2026-09-01']);
});
