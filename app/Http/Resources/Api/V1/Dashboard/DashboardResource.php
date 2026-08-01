<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Dashboard;

use App\DTOs\Dashboard\DashboardStats;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The six keys of FT-004's Metric Definitions, all six of them whatever the
 * account holds and even when every one reads 0 (BR-9, BR-10). Nothing else
 * reaches the caller: no project name, no task title, no identifier.
 *
 * @mixin DashboardStats
 */
final class DashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, int>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_projects' => $this->projects->total,
            'active_projects' => $this->projects->active,
            'total_tasks' => $this->tasks->total,
            'completed_tasks' => $this->tasks->completed,
            'pending_tasks' => $this->tasks->pending,
            'overdue_tasks' => $this->tasks->overdue,
        ];
    }
}
