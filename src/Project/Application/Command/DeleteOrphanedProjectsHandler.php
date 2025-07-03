<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

use App\Project\Application\Query\FindProjectsForDeletionQuery;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class DeleteOrphanedProjectsHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private MessageBusInterface $queryBus
    ) {
    }

    public function __invoke(DeleteOrphanedProjectsCommand $command): void
    {
        // Find all projects owned by the deleted user
        $query = new FindProjectsForDeletionQuery($command->getDeletedUserId());
        $envelope = $this->queryBus->dispatch($query);
        $projects = $envelope->last(\Symfony\Component\Messenger\Stamp\HandledStamp::class)->getResult();
        
        // Delete each project
        foreach ($projects as $project) {
            $deletedProject = $project->delete();
            $this->projectRepository->save($deletedProject);
        }
    }
}