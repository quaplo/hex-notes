<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Mapper;

use App\Project\Application\Mapper\ProjectDtoMapperInterface;
use App\Project\Domain\Model\Project;

final readonly class ProjectDtoMapper implements ProjectDtoMapperInterface
{
    public function toDto(Project $project): array
    {
        return [
            'id' => $project->getId()->toString(),
            'name' => $project->getName()->__toString(),
            'ownerId' => $project->getOwnerId()->toString(),
            'createdAt' => $project->getCreatedAt()->format('Y-m-d H:i:s'),
            'deletedAt' => $project->getDeletedAt()?->format('Y-m-d H:i:s'),
            'workers' => array_map(fn ($worker): array => [
                'userId' => $worker->getUserId()->toString(),
                'role' => $worker->getRole()->toString(),
                'addedBy' => $worker->getAddedBy()->toString(),
                'addedAt' => $worker->getCreatedAt()->format('Y-m-d H:i:s'),
            ], $project->getWorkers()),
        ];
    }
}
