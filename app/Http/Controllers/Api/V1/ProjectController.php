<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Constants\Projects\ProjectMessages;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Projects\IndexProjectsRequest;
use App\Http\Requests\Api\V1\Projects\StoreProjectRequest;
use App\Http\Requests\Api\V1\Projects\UpdateProjectRequest;
use App\Http\Resources\Api\V1\Projects\ProjectCollection;
use App\Http\Resources\Api\V1\Projects\ProjectResource;
use App\Http\Support\ApiResponse;
use App\Services\Projects\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProjectController extends Controller
{
    public function __construct(private readonly ProjectService $projects) {}

    public function index(IndexProjectsRequest $request): JsonResponse
    {
        $page = $this->projects->paginateForOwner($request->user(), $request->perPage());

        return ApiResponse::paginated(new ProjectCollection($page));
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projects->create($request->user(), $request->validated());

        return ApiResponse::created(ProjectResource::make($project));
    }

    public function show(Request $request, int $project): JsonResponse
    {
        return ApiResponse::success(
            ProjectResource::make($this->projects->findForOwner($request->user(), $project)),
        );
    }

    public function update(UpdateProjectRequest $request, int $project): JsonResponse
    {
        return ApiResponse::success(
            ProjectResource::make(
                $this->projects->update($request->user(), $project, $request->validated()),
            ),
        );
    }

    public function destroy(Request $request, int $project): JsonResponse
    {
        $this->projects->delete($request->user(), $project);

        return ApiResponse::success(message: ProjectMessages::DELETED);
    }
}
