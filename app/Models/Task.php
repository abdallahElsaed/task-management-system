<?php

namespace App\Models;

use App\Enums\Tasks\TaskPriority;
use App\Enums\Tasks\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The parent project is fixed at creation, so it is not fillable
 */
#[Fillable(['title', 'description', 'priority', 'status', 'due_date'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function isOverdue(): bool
    {
        return $this->status !== TaskStatus::Done
            && $this->due_date?->isBefore(today()) === true;
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function ownedBy(Builder $query, int $ownerId): void
    {
        $query->whereHas(
            'project',
            fn (Builder $projects) => $projects->where('user_id', $ownerId),
        );
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function dueSoonestFirst(Builder $query): void
    {
        $query->orderBy('due_date')->orderByDesc('created_at')->orderByDesc('id');
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function withStatus(Builder $query, TaskStatus $status): void
    {
        $query->where('status', $status);
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function withPriority(Builder $query, TaskPriority $priority): void
    {
        $query->where('priority', $priority);
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function inProject(Builder $query, int $projectId): void
    {
        $query->where('project_id', $projectId);
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function titleContains(Builder $query, string $term): void
    {
        $query->whereLike('title', '%'.$term.'%', caseSensitive: false);
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->where('due_date', '<', today()->toDateString())
            ->whereNot('status', TaskStatus::Done);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'due_date' => 'date',
        ];
    }
}
