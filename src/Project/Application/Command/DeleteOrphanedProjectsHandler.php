<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

use App\Project\Application\ReadModel\ProjectReadModelRepositoryInterface;
use App\Project\Domain\Model\Project;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\ValueObject\Uuid;

final readonly class DeleteOrphanedProjectsHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private ProjectReadModelRepositoryInterface $projectReadModelRepository
    ) {
    }

    public function __invoke(DeleteOrphanedProjectsCommand $deleteOrphanedProjectsCommand): void
    {
        $uuid = $deleteOrphanedProjectsCommand->getDeletedUserId();

        // Find only active projects owned by the deleted user
        $readModels = $this->projectReadModelRepository->findByOwnerId($uuid);

        // Delete each project
        foreach ($readModels as $readModel) {
            // Load the full aggregate from event store
            $projectId = Uuid::create($readModel->getId());
            $project = $this->projectRepository->load($projectId);

            if ($project instanceof Project) {
                $project->delete();
                $this->projectRepository->save($project);
            }
        }
    }
}
