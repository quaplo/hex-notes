<?php

namespace App\Infrastructure\Http\Mapper;

use App\Infrastructure\Http\Dto\ProjectDto;
use App\Project\Domain\Model\Project;

final class ProjectDtoMapper implements ProjectDtoMapperInterface
{
    public function toDto(Project $project): ProjectDto
    {
        return new ProjectDto(
            id: $project->getId()->toString(),
            name: $project->getName()->__toString(),
            ownerId: $project->getOwnerId()->toString(),
            createdAt: $project->getCreatedAt()->format('Y-m-d H:i:s'),
            deletedAt: $project->getDeletedAt()?->format('Y-m-d H:i:s')
        );
    }
}
