<?php

use App\Enums\Tasks\TaskPriority;
use App\Enums\Tasks\TaskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority', 20)->default(TaskPriority::Medium->value);
            $table->string('status', 20)->default(TaskStatus::Todo->value);
            $table->date('due_date');
            $table->timestamps();
            $table->softDeletes();

            // Drives the task count on the project list and the cascade delete.
            $table->index(['project_id', 'deleted_at']);
            // The default task list order and the dashboard's overdue count.
            $table->index(['due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
