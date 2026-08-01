<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class ProjectRepository implements ProjectRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Project>
     */
    public function paginateForOwner(int $ownerId, int $perPage): LengthAwarePaginator
    {
        return Project::ownedBy($ownerId)
            ->withTaskCount()
            ->newestFirst()
            ->paginate($perPage);
    }

    /**
     * Owner-scoped, and the soft-delete scope still applies, so another account's
     * project, an id that never existed and a deleted project all answer null.
     */
    public function findForOwner(int $ownerId, int $projectId): ?Project
    {
        return Project::ownedBy($ownerId)->withTaskCount()->find($projectId);
    }

    /**
     * The owner is set outside mass assignment, so a `user_id` in the request body
     * can never reach it (BR-1, EC-7).
     *
     * @param  array{name: string, description: ?string, status: string}  $attributes
     */
    public function create(int $ownerId, array $attributes): Project
    {
        $project = new Project($attributes);
        $project->user_id = $ownerId;
        $project->save();

        return $project;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Project $project, array $attributes): Project
    {
        $project->fill($attributes)->save();

        return $project;
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }
}
