<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Project\Model\Project;
use App\Domain\Project\Model\ProjectWorker;
use App\Domain\Project\ValueObject\ProjectName;
use App\Domain\Project\ValueObject\ProjectOwner;
use App\Domain\Project\ValueObject\ProjectRole;
use App\Domain\Project\ValueObject\UserId;
use App\Infrastructure\Persistence\Doctrine\Entity\ProjectEntity;
use App\Infrastructure\Persistence\Doctrine\Entity\ProjectWorkerEntity;
use App\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use App\Shared\ValueObject\Email;
use App\Shared\ValueObject\Uuid;
use Doctrine\ORM\EntityManagerInterface;

final class ProjectMapper
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function mapToDomain(ProjectEntity $entity): Project
    {
        $owner = $entity->getOwner();

        $project = new Project(
            new Uuid($entity->getId()),
            new ProjectName($entity->getName()),
            $entity->getCreatedAt(),
            ProjectOwner::create(UserId::fromString($owner->getId()), Email::fromString($owner->getEmail())),
            $entity->getDeletedAt()
        );

        foreach ($entity->getProjectWorkers() as $workerEntity) {
            $project->addWorker(
                new ProjectWorker(
                    new Uuid($workerEntity->getUser()->getId()),
                    ProjectRole::from($workerEntity->getRole()),
                    $workerEntity->getCreatedAt(),
                    new Uuid($workerEntity->getAddedBy()->getId())
                )
            );
        }

        return $project;
    }

    public function mapToEntity(Project $project): ProjectEntity
    {
        $entity = new ProjectEntity(
            $project->getId()->toString(),
            (string) $project->getName(),
            $project->getCreatedAt(),
            $project->getCreatedBy()->toString(),
            $project->getDeletedAt()
        );

        $userEntity = $this->em->getRepository(UserEntity::class)
            ->findOneById($project->getOwner()->getId()->toString());
        $entity->setOwner($userEntity);

        foreach ($project->getWorkers() as $worker) {
            $user = $this->em->getReference(UserEntity::class, $worker->getUserId()->toString());
            $addedBy = $this->em->getReference(
                UserEntity::class,
                $worker->getAddedBy()?->toString() ?? $worker->getUserId()->toString()
            );

            $workerEntity = new ProjectWorkerEntity(
                project: $entity,
                user: $user,
                role: $worker->getRole()->__toString(),
                addedBy: $addedBy,
                createdAt: $worker->getCreatedAt()
            );

            $entity->addProjectWorker($workerEntity);
        }

        return $entity;
    }

    public function updateEntity(ProjectEntity $entity, Project $project): void
    {
        $entity->setName((string) $project->getName());
        $entity->setDeletedAt($project->getDeletedAt());

        $entity->clearProjectWorkers();

        foreach ($project->getWorkers() as $worker) {
            $user = $this->em->getReference(UserEntity::class, $worker->getUserId()->toString());
            $addedBy = $this->em->getReference(
                UserEntity::class,
                $worker->getAddedBy()?->toString() ?? $worker->getUserId()->toString()
            );

            $workerEntity = new ProjectWorkerEntity(
                project: $entity,
                user: $user,
                role: $worker->getRole()->__toString(),
                addedBy: $addedBy,
                createdAt: $worker->getCreatedAt()
            );

            $entity->addProjectWorker($workerEntity);
        }
    }
}
