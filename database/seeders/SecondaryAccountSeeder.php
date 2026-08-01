<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * A second account holding data of its own, so the ownership rule (FT-002 BR-2) can
 * be checked by hand: log in as the demo account, ask for one of this account's
 * project ids, and the answer is a 404 rather than the record.
 *
 * Its dataset is deliberately small — it exists to be invisible to the demo account,
 * not to be browsed.
 */
class SecondaryAccountSeeder extends Seeder
{
    use WithoutModelEvents;

    public const string EMAIL = 'other@example.com';

    public const string PASSWORD = 'password123';

    public const string NAME = 'Nour Hassan';

    private const int TASK_COUNT = 3;

    public function run(): void
    {
        DB::transaction(function (): void {
            $owner = User::updateOrCreate(
                ['email' => self::EMAIL],
                ['name' => self::NAME, 'password' => self::PASSWORD],
            );

            $this->clearPreviousData($owner);

            $project = Project::factory()
                ->for($owner)
                ->create([
                    'name' => 'Internal Tooling',
                    'description' => "Nour's own work. The demo account cannot see any of it.",
                ]);

            Task::factory()
                ->count(self::TASK_COUNT)
                ->for($project)
                ->create();
        });
    }

    /**
     * A second run rebuilds rather than accumulates, for the same reason the demo
     * account's does (BR-8).
     */
    private function clearPreviousData(User $owner): void
    {
        $projectIds = Project::withTrashed()->ownedBy($owner->id)->pluck('id');

        if ($projectIds->isEmpty()) {
            return;
        }

        Task::withTrashed()->whereIn('project_id', $projectIds)->forceDelete();
        Project::withTrashed()->whereIn('id', $projectIds)->forceDelete();
    }
}
