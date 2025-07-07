<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Persistence\ReadModel;

use App\Shared\ValueObject\Uuid;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProjectReadModelRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function save(ProjectReadModelEntity $projectReadModelEntity): void
    {
        $this->entityManager->persist($projectReadModelEntity);
        $this->entityManager->flush();
    }

    public function findById(Uuid $uuid): ?ProjectReadModelEntity
    {
        return $this->entityManager->find(ProjectReadModelEntity::class, $uuid->toString());
    }

    public function findByOwnerId(Uuid $uuid): array
    {
        return $this->entityManager->getRepository(ProjectReadModelEntity::class)
            ->findBy(['ownerId' => $uuid->toString(), 'deletedAt' => null]);
    }

    public function findActiveProjects(): array
    {
        return $this->entityManager->getRepository(ProjectReadModelEntity::class)
            ->findBy(['deletedAt' => null]);
    }

    public function findByOwnerIdIncludingDeleted(Uuid $uuid): array
    {
        return $this->entityManager->getRepository(ProjectReadModelEntity::class)
            ->findBy(['ownerId' => $uuid->toString()]);
    }

    public function remove(ProjectReadModelEntity $projectReadModelEntity): void
    {
        $this->entityManager->remove($projectReadModelEntity);
        $this->entityManager->flush();
    }

    public function findProjectsWithWorker(Uuid $uuid): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        
        return $queryBuilder->select('p')
            ->from(ProjectReadModelEntity::class, 'p')
            ->where('JSON_CONTAINS(p.workers, :userId, \'$[*].userId\') = 1')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('userId', '"' . $uuid->toString() . '"')
            ->getQuery()
            ->getResult();
    }

    public function getProjectStatistics(Uuid $uuid): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        
        return $queryBuilder->select([
                'COUNT(p.id) as total_projects',
                'COUNT(CASE WHEN p.deletedAt IS NULL THEN 1 END) as active_projects',
                'COUNT(CASE WHEN p.deletedAt IS NOT NULL THEN 1 END) as deleted_projects'
            ])
            ->from(ProjectReadModelEntity::class, 'p')
            ->where('p.ownerId = :ownerId')
            ->setParameter('ownerId', $uuid->toString())
            ->getQuery()
            ->getSingleResult();
    }
}