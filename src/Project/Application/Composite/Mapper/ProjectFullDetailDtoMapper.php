<?php

declare(strict_types=1);

namespace App\Project\Application\Composite\Mapper;

use App\Infrastructure\Http\Dto\ProjectDto;
use App\Infrastructure\Http\Dto\UserDto;
use App\Project\Application\Composite\Dto\ProjectFullDetailDto;

final class ProjectFullDetailDtoMapper
{
    public function toDto(ProjectDto $project, UserDto $owner, array $workers = []): ProjectFullDetailDto
    {
        return new ProjectFullDetailDto($project, $owner, $workers);
    }
}
