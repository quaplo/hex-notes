<?php

namespace App\Project\Application\Composite\Mapper;

use App\Infrastructure\Http\Dto\ProjectDto;
use App\Infrastructure\Http\Dto\UserDto;
use App\Project\Application\Composite\Dto\ProjectFullDetailDto;

class ProjectFullDetailDtoMapper
{
    public function toDto(ProjectDto $project, UserDto $owner, array $workers = []): ProjectFullDetailDto
    {
        return new ProjectFullDetailDto($project, $owner, $workers);
    }
}
