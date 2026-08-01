<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Tasks;

use Illuminate\Http\Resources\Json\ResourceCollection;

final class TaskCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var class-string<TaskResource>
     */
    public $collects = TaskResource::class;
}
