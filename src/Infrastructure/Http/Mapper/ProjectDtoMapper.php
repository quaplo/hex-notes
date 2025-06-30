<?php

namespace App\Infrastructure\Http\Mapper;

use App\Infrastructure\Http\Dto\ProjectDto;
use App\Project\Domain\Model\Project;

final class ProjectDtoMapper
{
    public function toDto(Project $project): ProjectDto
    {
        return new ProjectDto(
            $project->getId()->toString(),
            $project->getName()->__toString(),
            $project->getOwnerEmail()->__toString(),
            $project->getCreatedAt()->format(DATE_ATOM),
        );
    }
}
