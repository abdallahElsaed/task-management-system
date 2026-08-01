<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\DTOs\Dashboard\ProjectCounts;
use App\Models\Project;
use App\Repositories\ProjectRepository;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

#[Bind(ProjectRepository::class)]
interface ProjectRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Project>
     */
    public function paginateForOwner(int $ownerId, int $perPage): LengthAwarePaginator;

    public function countsForOwner(int $ownerId): ProjectCounts;

    public function findForOwner(int $ownerId, int $projectId): ?Project;

    /**
     * @param  array{name: string, description: ?string, status: string}  $attributes
     */
    public function create(int $ownerId, array $attributes): Project;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Project $project, array $attributes): Project;

    public function delete(Project $project): void;
}
