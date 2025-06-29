<?php

namespace App\Infrastructure\Http\Mapper;

use App\Domain\Project\Model\Project;
use App\Infrastructure\Http\Dto\ProjectDto;

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
