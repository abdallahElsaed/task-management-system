<?php

namespace App\Models;

use App\Enums\Projects\ProjectStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The owner is deliberately absent from the fillable list: ownership is fixed at
 * creation and is never accepted from a request body.
 */
#[Fillable(['name', 'description', 'status'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The aliases `selectStatusCounts` returns its two numbers under. They are the
     * contract between that scope and whoever reads the row it produces.
     */
    public const string TOTAL_COUNT = 'total_count';

    public const string ACTIVE_COUNT = 'active_count';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Owner-scoping every read is what makes another account's project
     * indistinguishable from one that never existed.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function ownedBy(Builder $query, int $ownerId): void
    {
        $query->where('user_id', $ownerId);
    }

    /**
     * The id breaks ties because `created_at` holds no fractional seconds,
     * so two projects created inside the same second would otherwise come back
     * in an arbitrary order.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function newestFirst(Builder $query): void
    {
        $query->orderByDesc('created_at')->orderByDesc('id');
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function withTaskCount(Builder $query): void
    {
        $query->withCount('tasks');
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function withStatus(Builder $query, ProjectStatus $status): void
    {
        $query->where('status', $status);
    }

    /**
     * Both dashboard numbers in a single pass over whatever projects the caller has
     * already scoped to. `status` sits in no index, so counting the active projects
     * as a second statement reads the owner's rows a second time; as a conditional
     * aggregate it rides along with the total.
     *
     * The predicate repeats `withStatus` in SQL because an aggregate cannot call a
     * scope. They must be changed together.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function selectStatusCounts(Builder $query): void
    {
        $query->selectRaw('count(*) as '.self::TOTAL_COUNT)
            ->selectRaw(
                'count(case when status = ? then 1 end) as '.self::ACTIVE_COUNT,
                [ProjectStatus::Active->value],
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
            'status' => ProjectStatus::class,
        ];
    }
}
