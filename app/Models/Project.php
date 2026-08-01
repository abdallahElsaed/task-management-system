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
 * creation and is never accepted from a request body (BR-1, EC-7).
 */
#[Fillable(['name', 'description', 'status'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, SoftDeletes;

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
     * indistinguishable from one that never existed (BR-2, D-1).
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function ownedBy(Builder $query, int $ownerId): void
    {
        $query->where('user_id', $ownerId);
    }

    /**
     * BR-11. The id breaks ties because `created_at` holds no fractional seconds,
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
