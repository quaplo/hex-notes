<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Project\Model\Project;
use App\Infrastructure\Persistence\Doctrine\Entity\ProjectEntity;
use App\Domain\Project\ValueObject\ProjectName;
use App\Shared\ValueObject\Uuid;

final class ProjectMapper
{
    public function mapToDomain(ProjectEntity $entity): Project
    {
        return new Project(
            new Uuid($entity->getId()),
            new ProjectName($entity->getName()),
            $entity->getCreatedAt(),
            $entity->getDeletedAt()
        );
    }

    public function mapToEntity(Project $project): ProjectEntity
    {
        return new ProjectEntity(
            $project->getId()->toString(),
            (string) $project->getName(),
            $project->getCreatedAt(),
            $project->getDeletedAt()
        );
    }

    public function updateEntity(ProjectEntity $entity, Project $project): void
    {
        $entity->setName((string) $project->getName());
        $entity->setDeletedAt($project->getDeletedAt());
    }
}
