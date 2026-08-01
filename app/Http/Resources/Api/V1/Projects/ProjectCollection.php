<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Projects;

use Illuminate\Http\Resources\Json\ResourceCollection;

final class ProjectCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var class-string<ProjectResource>
     */
    public $collects = ProjectResource::class;
}
