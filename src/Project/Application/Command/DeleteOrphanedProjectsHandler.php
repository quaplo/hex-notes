<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

use App\Project\Application\Query\FindProjectsForDeletionQuery;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Application\QueryBus;

final readonly class DeleteOrphanedProjectsHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private QueryBus $queryBus
    ) {
    }

    public function __invoke(DeleteOrphanedProjectsCommand $deleteOrphanedProjectsCommand): void
    {
        // Find all projects owned by the deleted user
        $findProjectsForDeletionQuery = new FindProjectsForDeletionQuery($deleteOrphanedProjectsCommand->getDeletedUserId());
        $projects = $this->queryBus->dispatch($findProjectsForDeletionQuery);
        
        // Delete each project
        foreach ($projects as $project) {
            $deletedProject = $project->delete();
            $this->projectRepository->save($deletedProject);
        }
    }
}