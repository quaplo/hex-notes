<?php

declare(strict_types=1);

namespace App\Project\Application\Query;

use App\Project\Application\ReadModel\ProjectReadModelRepositoryInterface;
use App\Project\Domain\Model\Project;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\ValueObject\Uuid;

final readonly class FindProjectsForDeletionHandler
{
    public function __construct(
        private ProjectReadModelRepositoryInterface $projectReadModelRepository,
        private ProjectRepositoryInterface $projectRepository
    ) {
    }

    public function __invoke(FindProjectsForDeletionQuery $findProjectsForDeletionQuery): array
    {
        $ownerId = $findProjectsForDeletionQuery->ownerId;
        
        // First, find all projects owned by the user (including deleted ones)
        // We need the actual domain objects, not just read models
        $readModels = $this->projectReadModelRepository->findByOwnerIdIncludingDeleted($ownerId);
        
        $projects = [];
        foreach ($readModels as $readModel) {
            // Load the full aggregate from event store
            $projectId = Uuid::create($readModel->getId());
            $project = $this->projectRepository->load($projectId);
            
            if ($project instanceof Project && !$project->isDeleted()) {
                // Only include non-deleted projects for cleanup
                $projects[] = $project;
            }
        }
        
        return $projects;
    }
}