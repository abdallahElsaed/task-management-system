<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Dashboard\DashboardResource;
use App\Http\Support\ApiResponse;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    /**
     * The endpoint takes no input at all, so there is nothing for a form request
     * to validate: the caller is the whole query (FT-004 BR-1).
     */
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(
            DashboardResource::make($this->dashboard->statsForOwner($request->user())),
        );
    }
}
