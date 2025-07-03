<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Persistence\ReadModel;

use App\Shared\ValueObject\Uuid;
use Doctrine\ORM\EntityManagerInterface;

final class ProjectReadModelRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function save(ProjectReadModelEntity $project): void
    {
        $this->entityManager->persist($project);
        $this->entityManager->flush();
    }

    public function findById(Uuid $projectId): ?ProjectReadModelEntity
    {
        return $this->entityManager->find(ProjectReadModelEntity::class, $projectId->toString());
    }

    public function findByOwnerId(Uuid $ownerId): array
    {
        return $this->entityManager->getRepository(ProjectReadModelEntity::class)
            ->findBy(['ownerId' => $ownerId->toString(), 'deletedAt' => null]);
    }

    public function findActiveProjects(): array
    {
        return $this->entityManager->getRepository(ProjectReadModelEntity::class)
            ->findBy(['deletedAt' => null]);
    }

    public function findByOwnerIdIncludingDeleted(Uuid $ownerId): array
    {
        return $this->entityManager->getRepository(ProjectReadModelEntity::class)
            ->findBy(['ownerId' => $ownerId->toString()]);
    }

    public function remove(ProjectReadModelEntity $project): void
    {
        $this->entityManager->remove($project);
        $this->entityManager->flush();
    }

    public function findProjectsWithWorker(Uuid $userId): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        return $qb->select('p')
            ->from(ProjectReadModelEntity::class, 'p')
            ->where('JSON_CONTAINS(p.workers, :userId, \'$[*].userId\') = 1')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('userId', '"' . $userId->toString() . '"')
            ->getQuery()
            ->getResult();
    }

    public function getProjectStatistics(Uuid $ownerId): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        return $qb->select([
                'COUNT(p.id) as total_projects',
                'COUNT(CASE WHEN p.deletedAt IS NULL THEN 1 END) as active_projects',
                'COUNT(CASE WHEN p.deletedAt IS NOT NULL THEN 1 END) as deleted_projects'
            ])
            ->from(ProjectReadModelEntity::class, 'p')
            ->where('p.ownerId = :ownerId')
            ->setParameter('ownerId', $ownerId->toString())
            ->getQuery()
            ->getSingleResult();
    }
}