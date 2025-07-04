<?php

declare(strict_types=1);

namespace App\Project\Application\Query;

use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Infrastructure\Persistence\ReadModel\ProjectReadModelRepository;
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
        $ownerId = $query->ownerId;
        
        // First, find all projects owned by the user (including deleted ones)
        // We need the actual domain objects, not just read models
        $readModels = $this->readModelRepository->findByOwnerIdIncludingDeleted($ownerId);
        
        $projects = [];
        foreach ($readModels as $readModel) {
            // Load the full aggregate from event store
            $projectId = Uuid::create($readModel->getId());
            $project = $this->projectRepository->load($projectId);
            
            if ($project !== null && !$project->isDeleted()) {
                // Only include non-deleted projects for cleanup
                $projects[] = $project;
            }
        }
        
        return $projects;
    }
}