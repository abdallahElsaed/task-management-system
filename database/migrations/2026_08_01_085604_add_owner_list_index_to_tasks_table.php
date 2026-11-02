<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const string INDEX = 'tasks_deleted_due_index';

    /**
     * The default task list reads every project the caller owns at once and orders
     * by due date, so the live rows have to be walked in due-date order. The
     * existing single-column `due_date` index cannot skip the deleted ones, and
     * `project_id, deleted_at` cannot help a list that spans every project.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['deleted_at', 'due_date'], self::INDEX);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(self::INDEX);
        });
    }
};
