<?php

declare(strict_types=1);

namespace App\Services\Projects;

use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Enums\Projects\ProjectStatus;
use App\Exceptions\Projects\ProjectNotFoundException;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final readonly class ProjectService
{
    public function __construct(
        private ProjectRepositoryInterface $projects,
        private TaskRepositoryInterface $tasks,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Project>
     */
    public function paginateForOwner(User $owner, int $perPage): LengthAwarePaginator
    {
        return $this->projects->paginateForOwner($owner->id, $perPage);
    }

    /**
     * A project created without a stated status is Active. The default is
     * applied here rather than left to the column, so the created response carries
     * the status without a second read.
     *
     * @param  array{name: string, description?: ?string, status?: ?string}  $data
     */
    public function create(User $owner, array $data): Project
    {
        return $this->projects->create($owner->id, [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? ProjectStatus::Active->value,
        ]);
    }

    /**
     * The single way a project is reached, so every unreachable id — someone
     * else's, one that never existed, one already deleted — answers identically.
     *
     * @throws ProjectNotFoundException
     */
    public function findForOwner(User $owner, int $projectId): Project
    {
        return $this->projects->findForOwner($owner->id, $projectId)
            ?? throw new ProjectNotFoundException;
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ProjectNotFoundException
     */
    public function update(User $owner, int $projectId, array $data): Project
    {
        return $this->projects->update($this->findForOwner($owner, $projectId), $data);
    }

    /**
     * A project and its tasks leave every list in the same moment, so both
     * writes land together or neither does.
     *
     * @throws ProjectNotFoundException
     */
    public function delete(User $owner, int $projectId): void
    {
        $project = $this->findForOwner($owner, $projectId);

        DB::transaction(function () use ($project): void {
            $this->tasks->deleteAllForProject($project->id);
            $this->projects->delete($project);
        });
    }
}
