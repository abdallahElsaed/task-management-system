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
     * The aliases `selectStatusCounts` returns its four numbers under. They are the
     * contract between that scope and whoever reads the row it produces.
     */
    public const string TOTAL_COUNT = 'total_count';

    public const string COMPLETED_COUNT = 'completed_count';

    public const string PENDING_COUNT = 'pending_count';

    public const string OVERDUE_COUNT = 'overdue_count';

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
     * Pending is Todo and In Progress together (FT-004 Metric Definitions, D-1).
     * An overdue task is pending as well: the two counts overlap rather than
     * divide the work between them (FT-004 BR-3).
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->whereIn('status', [TaskStatus::Todo, TaskStatus::InProgress]);
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
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function selectStatusCounts(Builder $query): void
    {
        $query->selectRaw('count(*) as '.self::TOTAL_COUNT)
            ->selectRaw(
                'count(case when status = ? then 1 end) as '.self::COMPLETED_COUNT,
                [TaskStatus::Done->value],
            )
            ->selectRaw(
                'count(case when status in (?, ?) then 1 end) as '.self::PENDING_COUNT,
                [TaskStatus::Todo->value, TaskStatus::InProgress->value],
            )
            ->selectRaw(
                'count(case when status <> ? and due_date < ? then 1 end) as '.self::OVERDUE_COUNT,
                [TaskStatus::Done->value, today()->toDateString()],
            );
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
