<?php

declare(strict_types=1);

namespace App\Project\Application\Query\Find;

use App\Project\Application\ReadModel\ProjectReadModelRepositoryInterface;

final readonly class FindProjectsByOwnerOptimizedHandler
{
    public function __construct(
        private ProjectReadModelRepositoryInterface $projectReadModelRepository,
    ) {
    }

    public function __invoke(FindProjectsByOwnerQuery $findProjectsByOwnerQuery): array
    {
        $ownerId = $findProjectsByOwnerQuery->ownerId;
        $readModels = $this->projectReadModelRepository->findByOwnerId($ownerId);

        return array_map(fn ($readModel): array => [
            'id' => $readModel->getId(),
            'name' => $readModel->getName(),
            'ownerId' => $readModel->getOwnerId(),
            'createdAt' => $readModel->getCreatedAt(),
            'deletedAt' => $readModel->getDeletedAt(),
            'workers' => $readModel->getWorkers(),
            'version' => $readModel->getVersion(),
            'workersCount' => \count($readModel->getWorkers()),
            'isDeleted' => $readModel->isDeleted(),
        ], $readModels);
    }
}
