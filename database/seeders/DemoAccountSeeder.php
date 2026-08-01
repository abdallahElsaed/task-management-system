<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Projects\ProjectStatus;
use App\Enums\Tasks\TaskPriority;
use App\Enums\Tasks\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The account a reviewer logs in as, and the dataset behind it (BR-6, BR-7).
 *
 * The dataset is written out row by row rather than generated at random, because
 * BR-7 states it by exact counts: 4 projects covering all three statuses, and 25
 * tasks covering all three priorities and all three statuses, of which at least 3
 * are overdue and at least 5 are done. A fixed table is the only way those numbers
 * hold on every run, and it is also the only way a reviewer can read the seeder and
 * see what the dashboard is about to return.
 *
 * Due dates are held as an offset in days from the day the seeder runs, so the
 * overdue tasks are still overdue however long after seeding the reviewer looks.
 */
class DemoAccountSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Published in the README, so a reviewer never has to register first (BR-6, D-4).
     */
    public const string EMAIL = 'demo@example.com';

    public const string PASSWORD = 'password123';

    public const string NAME = 'Demo Reviewer';

    public function run(): void
    {
        DB::transaction(function (): void {
            $demo = $this->account();

            $this->clearPreviousData($demo);

            foreach ($this->dataset() as $definition) {
                $this->createProject($demo, $definition);
            }
        });
    }

    /**
     * The account is updated rather than recreated, so the published password keeps
     * working whatever else a second run rebuilds (BR-8, EC-4).
     */
    private function account(): User
    {
        return User::updateOrCreate(
            ['email' => self::EMAIL],
            ['name' => self::NAME, 'password' => self::PASSWORD],
        );
    }

    /**
     * A second run rebuilds the dataset instead of adding to it, which is what keeps
     * the counts in BR-7 exact however many times the seeder is called. The rows go
     * for good rather than into the soft-deleted state, so nothing is left behind
     * for a query that reads trashed records to find.
     */
    private function clearPreviousData(User $demo): void
    {
        $projectIds = Project::withTrashed()->ownedBy($demo->id)->pluck('id');

        if ($projectIds->isEmpty()) {
            return;
        }

        Task::withTrashed()->whereIn('project_id', $projectIds)->forceDelete();
        Project::withTrashed()->whereIn('id', $projectIds)->forceDelete();
    }

    /**
     * @param  array{name: string, description: string, status: ProjectStatus, tasks: list<array{title: string, description: string, priority: TaskPriority, status: TaskStatus, due_in_days: int}>}  $definition
     */
    private function createProject(User $demo, array $definition): void
    {
        $project = Project::factory()
            ->for($demo)
            ->create([
                'name' => $definition['name'],
                'description' => $definition['description'],
                'status' => $definition['status'],
            ]);

        foreach ($definition['tasks'] as $task) {
            Task::factory()
                ->for($project)
                ->create([
                    'title' => $task['title'],
                    'description' => $task['description'],
                    'priority' => $task['priority'],
                    'status' => $task['status'],
                    'due_date' => today()->addDays($task['due_in_days']),
                ]);
        }
    }

    /**
     * 4 projects and 25 tasks.
     *
     * What the table adds up to, so a change to it can be read against BR-7:
     * projects — 2 active, 1 completed, 1 archived; tasks — 9 done, 5 in progress
     * and 11 todo, of which 5 are overdue; priorities — 9 high, 9 medium, 7 low.
     *
     * Several titles are written in Arabic, so FT-003's search and the digest email
     * can both be checked against a non-Latin script by hand (⚠️ A-3).
     *
     * @return list<array{name: string, description: string, status: ProjectStatus, tasks: list<array{title: string, description: string, priority: TaskPriority, status: TaskStatus, due_in_days: int}>}>
     */
    private function dataset(): array
    {
        return [
            [
                'name' => 'Website Redesign',
                'description' => 'Rebuild the marketing site on the new design system.',
                'status' => ProjectStatus::Active,
                'tasks' => [
                    $this->task('Audit the current information architecture', 'Map every page that survives the rebuild.', TaskPriority::High, TaskStatus::Done, -30),
                    $this->task('Agree the new colour palette', 'Sign off the accessible contrast pairs.', TaskPriority::Medium, TaskStatus::Done, -21),
                    $this->task('Rebuild the pricing page', 'Three tiers, an annual toggle, and no hard-coded copy.', TaskPriority::High, TaskStatus::InProgress, -5),
                    $this->task('إعادة تصميم صفحة الاتصال', 'نموذج أقصر مع رسائل تحقق أوضح.', TaskPriority::Medium, TaskStatus::Todo, -2),
                    $this->task('Write copy for the about page', 'Two paragraphs and the team list.', TaskPriority::Low, TaskStatus::Todo, 7),
                    $this->task('Compress the hero images', 'Under 200 KB each, with no visible loss.', TaskPriority::Low, TaskStatus::Todo, 14),
                    $this->task('Set up redirects for the old URLs', 'Every retired page points somewhere useful.', TaskPriority::High, TaskStatus::InProgress, 3),
                    $this->task('Run an accessibility pass', 'Keyboard order, labels and contrast on every template.', TaskPriority::Medium, TaskStatus::Todo, 21),
                ],
            ],
            [
                'name' => 'Mobile App Launch',
                'description' => 'Ship the first public build to both stores.',
                'status' => ProjectStatus::Active,
                'tasks' => [
                    $this->task('Finalise the onboarding flow', 'Four screens, skippable after the first.', TaskPriority::High, TaskStatus::Done, -18),
                    $this->task('مراجعة نصوص الإشعارات', 'توحيد نبرة الرسائل قبل الإطلاق.', TaskPriority::Medium, TaskStatus::InProgress, -9),
                    $this->task('Ship the beta to TestFlight', 'Internal testers first, then the waiting list.', TaskPriority::High, TaskStatus::Todo, -1),
                    $this->task('Wire up push notifications', 'Device registration and one test payload.', TaskPriority::High, TaskStatus::InProgress, 5),
                    $this->task('Localise the app store listing', 'English and Arabic, screenshots included.', TaskPriority::Medium, TaskStatus::Todo, 12),
                    $this->task('Draft the release notes', 'What changed, in language a user recognises.', TaskPriority::Low, TaskStatus::Todo, 18),
                    $this->task('Add crash reporting', 'Symbol upload wired into the release build.', TaskPriority::Medium, TaskStatus::Done, -12),
                ],
            ],
            [
                'name' => 'Q2 Marketing Campaign',
                'description' => 'The spring push, now closed out.',
                'status' => ProjectStatus::Completed,
                'tasks' => [
                    $this->task('Book the newsletter slot', 'Second week, the partner list.', TaskPriority::Medium, TaskStatus::Done, -45),
                    $this->task('Design the campaign banners', 'Three sizes for the ad network.', TaskPriority::High, TaskStatus::Done, -40),
                    $this->task('حملة إعلانات جوجل', 'ضبط الكلمات المفتاحية والميزانية اليومية.', TaskPriority::High, TaskStatus::Done, -35),
                    $this->task('Report on campaign reach', 'Impressions, clicks and sign-ups on one page.', TaskPriority::Low, TaskStatus::Done, -28),
                    $this->task('Archive the campaign assets', 'Move the finals into shared storage.', TaskPriority::Low, TaskStatus::Todo, -20),
                    $this->task('Collect testimonials from beta users', 'Five quotes, cleared for public use.', TaskPriority::Medium, TaskStatus::Todo, 9),
                ],
            ],
            [
                'name' => 'Legacy Portal Migration',
                'description' => 'Retire the old customer portal. Parked for now.',
                'status' => ProjectStatus::Archived,
                'tasks' => [
                    $this->task('Export the legacy database', 'A full dump, checksummed and stored twice.', TaskPriority::High, TaskStatus::Done, -60),
                    $this->task('Map the old roles to the new ones', 'Nine legacy roles onto four.', TaskPriority::Medium, TaskStatus::InProgress, 30),
                    $this->task('نقل ملفات المستخدمين', 'نقل المرفقات مع الحفاظ على الروابط القديمة.', TaskPriority::Low, TaskStatus::Todo, 45),
                    $this->task('Decommission the old server', 'After thirty days with no traffic.', TaskPriority::Low, TaskStatus::Todo, 60),
                ],
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, priority: TaskPriority, status: TaskStatus, due_in_days: int}
     */
    private function task(
        string $title,
        string $description,
        TaskPriority $priority,
        TaskStatus $status,
        int $dueInDays,
    ): array {
        return [
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'status' => $status,
            'due_in_days' => $dueInDays,
        ];
    }
}
