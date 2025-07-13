<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Mapper;

use DateTimeImmutable;
use App\Project\Domain\Model\Project;
use App\Shared\Application\Dto\ProjectDto;
use App\Shared\Application\Mapper\ProjectDtoMapperInterface;

final readonly class SharedProjectDtoMapper implements ProjectDtoMapperInterface
{
    public function toDto(Project $project): ProjectDto
    {
        return new ProjectDto(
            id: $project->getId()->toString(),
            name: $project->getName()->__toString(),
            ownerId: $project->getOwnerId()->toString(),
            workers: array_map(fn($worker): array => [
                'userId' => $worker->getUserId()->toString(),
                'role' => (string)$worker->getRole(),
                'addedBy' => $worker->getAddedBy()->toString(),
                'addedAt' => $worker->getCreatedAt()->format('Y-m-d H:i:s')
            ], $project->getWorkers()),
            isDeleted: $project->getDeletedAt() instanceof DateTimeImmutable
        );
    }
}
