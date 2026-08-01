<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->project = Project::factory()->for($this->owner)->create();
});

it('returns every field of a task', function () {
    $task = Task::factory()->for($this->project)->create([
        'title' => 'Fix login bug',
        'description' => 'The form rejects valid credentials',
        'due_date' => '2026-08-15',
    ]);

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.show', $task))
        ->assertOk()
        ->assertJsonPath('data.id', $task->id)
        ->assertJsonPath('data.project_id', $this->project->id)
        ->assertJsonPath('data.title', 'Fix login bug')
        ->assertJsonPath('data.description', 'The form rejects valid credentials')
        ->assertJsonPath('data.priority', 'medium')
        ->assertJsonPath('data.status', 'todo')
        ->assertJsonPath('data.due_date', '2026-08-15')
        ->assertJsonStructure(['data' => ['is_overdue', 'created_at', 'updated_at']]);
});

it('returns the due date as a calendar date with no time of day', function () {
    $task = Task::factory()->for($this->project)->create(['due_date' => '2026-08-15']);

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.show', $task))
        ->assertOk()
        ->assertJsonPath('data.due_date', '2026-08-15');
});

it('marks a task overdue when its due date has passed and it is not done', function () {
    $task = Task::factory()->for($this->project)->overdue()->create();

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.show', $task))
        ->assertOk()
        ->assertJsonPath('data.is_overdue', true);
});

it('never marks a done task overdue, however old its due date', function () {
    $task = Task::factory()->for($this->project)->done()->create([
        'due_date' => '2020-01-15',
    ]);

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.show', $task))
        ->assertOk()
        ->assertJsonPath('data.is_overdue', false);
});

it('does not mark a task due today as overdue', function () {
    $task = Task::factory()->for($this->project)->create([
        'due_date' => today()->toDateString(),
    ]);

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.show', $task))
        ->assertOk()
        ->assertJsonPath('data.is_overdue', false);
});

it('answers a task under another account\'s project as not found', function () {
    $task = Task::factory()->create();

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('tasks.show', $task))
        ->assertNotFound()
        ->assertExactJson(['success' => false, 'message' => 'Task not found.']);
});

it('answers an id that never existed exactly as it answers another account\'s task', function () {
    $foreign = Task::factory()->create();

    $unknown = $this->withToken(tokenFor($this->owner))->getJson(route('tasks.show', 999999));
    $foreignResponse = $this->withToken(tokenFor($this->owner))->getJson(route('tasks.show', $foreign));

    $unknown->assertNotFound();
    $foreignResponse->assertNotFound();

    expect($unknown->json())->toBe($foreignResponse->json());
});

it('returns an Arabic title unchanged', function () {
    $title = 'تصميم الصفحة الرئيسية';
    $task = Task::factory()->for($this->project)->create(['title' => $title]);

    $response = $this->withToken(tokenFor($this->owner))->getJson(route('tasks.show', $task));

    $response->assertOk()->assertJsonPath('data.title', $title);

    expect($response->getContent())->toContain($title);
});
