<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Constants\Tasks\TaskMessages;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Tasks\IndexTasksRequest;
use App\Http\Requests\Api\V1\Tasks\StoreTaskRequest;
use App\Http\Requests\Api\V1\Tasks\UpdateTaskRequest;
use App\Http\Resources\Api\V1\Tasks\TaskCollection;
use App\Http\Resources\Api\V1\Tasks\TaskResource;
use App\Http\Support\ApiResponse;
use App\Services\Tasks\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TaskController extends Controller
{
    public function __construct(private readonly TaskService $tasks) {}

    public function index(IndexTasksRequest $request): JsonResponse
    {
        $page = $this->tasks->paginateForOwner(
            $request->user(),
            $request->filters(),
            $request->perPage(),
        );

        return ApiResponse::paginated(new TaskCollection($page->tasks), $page->message);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        return ApiResponse::created(
            TaskResource::make($this->tasks->create($request->validated())),
        );
    }

    public function show(Request $request, int $task): JsonResponse
    {
        return ApiResponse::success(
            TaskResource::make($this->tasks->findForOwner($request->user(), $task)),
        );
    }

    public function update(UpdateTaskRequest $request, int $task): JsonResponse
    {
        return ApiResponse::success(
            TaskResource::make(
                $this->tasks->update($request->user(), $task, $request->validated()),
            ),
        );
    }

    public function destroy(Request $request, int $task): JsonResponse
    {
        $this->tasks->delete($request->user(), $task);

        return ApiResponse::success(message: TaskMessages::DELETED);
    }
}
