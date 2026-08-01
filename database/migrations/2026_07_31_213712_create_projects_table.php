<?php

use App\Enums\Projects\ProjectStatus;
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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 20)->default(ProjectStatus::Active->value);
            $table->timestamps();
            $table->softDeletes();

            // Every list read is `user_id = ? AND deleted_at IS NULL ORDER BY created_at DESC`,
            // so this one index answers it without a filesort.
            $table->index(['user_id', 'deleted_at', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
