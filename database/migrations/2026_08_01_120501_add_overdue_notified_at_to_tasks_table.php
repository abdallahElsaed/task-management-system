<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table){
            $table->timestamp('overdue_notified_at')->nullable();
            $table->index(['overdue_notified_at', 'due_date'], 'tasks_overdue_sweep_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_overdue_sweep_index');
            $table->dropColumn('overdue_notified_at');
        });
    }
};
