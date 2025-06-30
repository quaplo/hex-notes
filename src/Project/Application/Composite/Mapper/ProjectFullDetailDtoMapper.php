<?php

namespace App\Application\Composite\Mapper;

use App\Infrastructure\Http\Dto\UserDto;

class ProjectFullDetailDtoMapper
{
    public function toDto(ProjectDto $project, UserDto $owner, array $workers = []): ProjectFullDetailDto
    {
        return new ProjectFullDetailDto($project, $owner, $workers);
    }
} 