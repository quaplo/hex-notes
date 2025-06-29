<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Project\Model\Project;
use App\Domain\Project\Repository\ProjectRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Entity\ProjectEntity;
use App\Infrastructure\Persistence\Doctrine\Mapper\ProjectMapper;
use App\Shared\ValueObject\Uuid;
use Doctrine\ORM\EntityManagerInterface;

final class ProjectRepository implements ProjectRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProjectMapper $mapper,
    ) {
    }

    public function save(Project $project): void
    {
        $entity = $this->em->getRepository(ProjectEntity::class)->find($project->getId()->toString());

        if (!$entity) {
            $entity = $this->mapper->mapToEntity($project);
        } else {
            $this->mapper->updateEntity($entity, $project);
        }

        $this->em->persist($entity);
        $this->em->flush();
    }

    public function findById(Uuid $id): ?Project
    {
        $entity = $this->em
            ->getRepository(ProjectEntity::class)
            ->findOneBy(['id' => $id->toString()]);

        return $entity ? $this->mapper->mapToDomain($entity) : null;
    }

    public function findAll(): array
    {
        $entities = $this->em->getRepository(ProjectEntity::class)->findAll();

        return array_map([$this->mapper, 'mapToDomain'], $entities);
    }

    public function delete(Project $project): void
    {
        $this->save($project->delete());
    }
}
