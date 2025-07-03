<?php

declare(strict_types=1);

namespace App\Project\Application\Query;

use App\Project\Infrastructure\Persistence\ReadModel\ProjectReadModelRepository;
use App\Shared\ValueObject\Uuid;

final class FindProjectsByOwnerOptimizedHandler
{
    public function __construct(
        private readonly ProjectReadModelRepository $readModelRepository
    ) {
    }

    public function __invoke(FindProjectsByOwnerQuery $query): array
    {
        $ownerId = $query->ownerId;
        $readModels = $this->readModelRepository->findByOwnerId($ownerId);
        
        return array_map(function ($readModel) {
            return [
                'id' => $readModel->getId(),
                'name' => $readModel->getName(),
                'ownerId' => $readModel->getOwnerId(),
                'createdAt' => $readModel->getCreatedAt(),
                'deletedAt' => $readModel->getDeletedAt(),
                'workers' => $readModel->getWorkers(),
                'version' => $readModel->getVersion(),
                'workersCount' => count($readModel->getWorkers()),
                'isDeleted' => $readModel->isDeleted()
            ];
        }, $readModels);
    }
}