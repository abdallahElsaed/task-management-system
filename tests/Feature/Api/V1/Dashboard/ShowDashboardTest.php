<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->owner = User::factory()->create();
});

it('answers a brand-new account with six zeros', function () {
    $this->withToken(tokenFor($this->owner))
        ->getJson(route('dashboard.show'))
        ->assertOk()
        ->assertExactJson([
            'success' => true,
            'data' => [
                'total_projects' => 0,
                'active_projects' => 0,
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'pending_tasks' => 0,
                'overdue_tasks' => 0,
            ],
        ]);
});

it('counts projects by status and tasks by status together', function () {
    $active = Project::factory()->count(3)->for($this->owner)->create();
    Project::factory()->for($this->owner)->completed()->create();
    Project::factory()->for($this->owner)->archived()->create();

    Task::factory()->count(6)->for($active->first())->create(['due_date' => '2099-01-01']);
    Task::factory()->count(4)->for($active->first())->done()->create(['due_date' => '2099-01-01']);

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('dashboard.show'))
        ->assertOk()
        ->assertJsonPath('data.total_projects', 5)
        ->assertJsonPath('data.active_projects', 3)
        ->assertJsonPath('data.total_tasks', 10)
        ->assertJsonPath('data.completed_tasks', 4)
        ->assertJsonPath('data.pending_tasks', 6)
        ->assertJsonPath('data.overdue_tasks', 0);
});

it('returns the six counts and nothing else on an account holding data', function () {
    $project = Project::factory()->for($this->owner)->create();
    Task::factory()->count(3)->for($project)->create(['due_date' => '2099-01-01']);

    $response = $this->withToken(tokenFor($this->owner))->getJson(route('dashboard.show'));

    $response->assertOk()->assertJsonCount(6, 'data');

    expect(array_keys($response->json('data')))->toBe([
        'total_projects',
        'active_projects',
        'total_tasks',
        'completed_tasks',
        'pending_tasks',
        'overdue_tasks',
    ]);
});

it('always reports completed and pending adding up to the total', function () {
    $project = Project::factory()->for($this->owner)->create();

    Task::factory()->count(4)->for($project)->create(['due_date' => '2099-01-01']);
    Task::factory()->count(3)->for($project)->inProgress()->create(['due_date' => '2020-01-01']);
    Task::factory()->count(5)->for($project)->done()->create(['due_date' => '2020-01-01']);

    $counts = $this->withToken(tokenFor($this->owner))
        ->getJson(route('dashboard.show'))
        ->assertOk()
        ->json('data');

    expect($counts['completed_tasks'] + $counts['pending_tasks'])->toBe($counts['total_tasks']);
});

it('reports zero active projects when nothing is Active', function () {
    Project::factory()->count(2)->for($this->owner)->archived()->create();
    Project::factory()->for($this->owner)->completed()->create();

    $response = $this->withToken(tokenFor($this->owner))->getJson(route('dashboard.show'));

    $response->assertOk()
        ->assertJsonPath('data.total_projects', 3)
        ->assertJsonPath('data.active_projects', 0);

    expect($response->json('data'))->toHaveKey('active_projects');
});

it('reports four zeros for the task counts on an account with projects but no tasks', function () {
    Project::factory()->count(3)->for($this->owner)->create();

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('dashboard.show'))
        ->assertOk()
        ->assertJsonPath('data.total_projects', 3)
        ->assertJsonPath('data.total_tasks', 0)
        ->assertJsonPath('data.completed_tasks', 0)
        ->assertJsonPath('data.pending_tasks', 0)
        ->assertJsonPath('data.overdue_tasks', 0);
});

it('already counts a task created a moment earlier', function () {
    $project = Project::factory()->for($this->owner)->create();
    $token = tokenFor($this->owner);

    $this->withToken($token)
        ->getJson(route('dashboard.show'))
        ->assertOk()
        ->assertJsonPath('data.total_tasks', 0);

    $this->withToken($token)
        ->postJson(route('tasks.store'), taskPayload($project->id))
        ->assertCreated();

    $this->withToken($token)
        ->getJson(route('dashboard.show'))
        ->assertOk()
        ->assertJsonPath('data.total_tasks', 1);
});

it('never counts another account\'s projects or tasks', function () {
    Project::factory()
        ->count(4)
        ->has(Task::factory()->count(5)->state(['due_date' => '2099-01-01']))
        ->create();

    $project = Project::factory()->for($this->owner)->create();
    Task::factory()->count(2)->for($project)->create(['due_date' => '2099-01-01']);

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('dashboard.show'))
        ->assertOk()
        ->assertJsonPath('data.total_projects', 1)
        ->assertJsonPath('data.total_tasks', 2);
});

it('drops a deleted project and its twelve tasks in the same moment', function () {
    $deleted = Project::factory()->for($this->owner)->create();
    $kept = Project::factory()->for($this->owner)->create();

    Task::factory()->count(12)->for($deleted)->create(['due_date' => '2099-01-01']);
    Task::factory()->for($kept)->create(['due_date' => '2099-01-01']);

    $token = tokenFor($this->owner);

    $this->withToken($token)
        ->getJson(route('dashboard.show'))
        ->assertOk()
        ->assertJsonPath('data.total_projects', 2)
        ->assertJsonPath('data.total_tasks', 13);

    $this->withToken($token)->deleteJson(route('projects.destroy', $deleted))->assertOk();

    $this->withToken($token)
        ->getJson(route('dashboard.show'))
        ->assertOk()
        ->assertJsonPath('data.total_projects', 1)
        ->assertJsonPath('data.total_tasks', 1);
});

it('excludes the tasks of a project deleted without its tasks going with it', function () {
    $project = Project::factory()->for($this->owner)->create();
    Task::factory()->count(4)->for($project)->create(['due_date' => '2099-01-01']);

    // Deleting the model directly leaves the task rows live, which is the only
    // way to prove the counts read through to the project's own deleted_at.
    $project->delete();

    $this->withToken(tokenFor($this->owner))
        ->getJson(route('dashboard.show'))
        ->assertOk()
        ->assertJsonPath('data.total_projects', 0)
        ->assertJsonPath('data.total_tasks', 0);
});

it('excludes tasks deleted on their own', function () {
    $project = Project::factory()->for($this->owner)->create();
    $tasks = Task::factory()->count(10)->for($project)->create(['due_date' => '2099-01-01']);

    $token = tokenFor($this->owner);

    $tasks->take(3)->each(function (Task $task) use ($token): void {
        $this->withToken($token)->deleteJson(route('tasks.destroy', $task))->assertOk();
    });

    $this->withToken($token)
        ->getJson(route('dashboard.show'))
        ->assertOk()
        ->assertJsonPath('data.total_tasks', 7);
});

it('answers with a fixed number of queries however much the account holds', function () {
    Project::factory()
        ->count(5)
        ->for($this->owner)
        ->has(Task::factory()->count(10)->state(['due_date' => '2099-01-01']))
        ->create();

    $token = tokenFor($this->owner);

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    $this->withToken($token)->getJson(route('dashboard.show'))->assertOk();

    // Three for the token and six counts — none of them grows with the number
    // of projects or tasks behind them.
    expect($queries)->toBeLessThan(10);
});
