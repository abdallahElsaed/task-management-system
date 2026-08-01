<?php

namespace Database\Factories;

use App\Enums\Tasks\TaskPriority;
use App\Enums\Tasks\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'priority' => TaskPriority::Medium,
            'status' => TaskStatus::Todo,
            'due_date' => fake()->dateTimeBetween('-1 month', '+2 months')->format('Y-m-d'),
        ];
    }

    /**
     * Indicate that the work is finished.
     */
    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::Done,
        ]);
    }

    /**
     * Indicate that the work has started.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::InProgress,
        ]);
    }

    /**
     * A due date already past, with a status that still counts as outstanding (BR-12).
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => today()->subWeek()->toDateString(),
            'status' => TaskStatus::Todo,
        ]);
    }
}
