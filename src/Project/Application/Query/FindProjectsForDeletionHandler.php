<?php

declare(strict_types=1);

namespace App\Project\Application\Query;

use App\Project\Infrastructure\Persistence\ReadModel\ProjectReadModelRepository;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\ValueObject\Uuid;

final class FindProjectsForDeletionHandler
{
    public function __construct(
        private readonly ProjectReadModelRepository $readModelRepository,
        private readonly ProjectRepositoryInterface $projectRepository
    ) {
    }

    public function __invoke(FindProjectsForDeletionQuery $query): array
    {
        // First, get project IDs from read model for performance
        $readModels = $this->readModelRepository->findByOwnerId($query->ownerId);
        
        // Then load full domain objects from event store
        $projects = [];
        foreach ($readModels as $readModel) {
            $projectId = Uuid::create($readModel->getId());
            $project = $this->projectRepository->load($projectId);
            if ($project && !$project->isDeleted()) {
                $projects[] = $project;
            }
        }
        
        return $projects;
    }
}