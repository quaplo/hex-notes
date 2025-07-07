<?php

declare(strict_types=1);

namespace App\Project\Application\EventHandler;

use App\Project\Domain\Event\ProjectCreatedEvent;
use App\Project\Domain\Event\ProjectDeletedEvent;
use App\Project\Domain\Event\ProjectRenamedEvent;
use App\Project\Domain\Event\ProjectWorkerAddedEvent;
use App\Project\Domain\Event\ProjectWorkerRemovedEvent;
use App\Project\Infrastructure\Projection\ProjectReadModelProjection;
use App\Shared\Domain\Event\DomainEvent;
use Psr\Log\LoggerInterface;

final readonly class ProjectEventHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private ProjectReadModelProjection $projectReadModelProjection
    ) {
    }

    public function __invoke(DomainEvent $domainEvent): void
    {
        // First update read model projection
        $this->projectReadModelProjection->handle($domainEvent);

        // Then handle other side effects
        match ($domainEvent::class) {
            ProjectCreatedEvent::class => $this->handleProjectCreated($domainEvent),
            ProjectRenamedEvent::class => $this->handleProjectRenamed($domainEvent),
            ProjectDeletedEvent::class => $this->handleProjectDeleted($domainEvent),
            ProjectWorkerAddedEvent::class => $this->handleProjectWorkerAdded($domainEvent),
            ProjectWorkerRemovedEvent::class => $this->handleProjectWorkerRemoved($domainEvent),
            default => $this->logger->info('Unhandled project event', ['event' => $domainEvent::class])
        };
    }
    
    public function handle(DomainEvent $domainEvent): void
    {
        $this->__invoke($domainEvent);
    }

    private function handleProjectCreated(ProjectCreatedEvent $projectCreatedEvent): void
    {
        $this->logger->info('Project created - side effects processing', [
            'projectId' => $projectCreatedEvent->getProjectId()->toString(),
            'name' => $projectCreatedEvent->getName()->__toString()
        ]);

        // Additional side effects:
        // - Send welcome email to project owner
        // - Create audit log entry
        // - Send notifications
        // - Update external systems
    }

    private function handleProjectRenamed(ProjectRenamedEvent $projectRenamedEvent): void
    {
        $this->logger->info('Project renamed - side effects processing', [
            'projectId' => $projectRenamedEvent->getProjectId()->toString(),
            'oldName' => $projectRenamedEvent->getOldName()->__toString(),
            'newName' => $projectRenamedEvent->getNewName()->__toString()
        ]);

        // Additional side effects:
        // - Send notifications to project members
        // - Update search index
        // - Update external integrations
    }

    private function handleProjectDeleted(ProjectDeletedEvent $projectDeletedEvent): void
    {
        $this->logger->info('Project deleted - side effects processing', [
            'projectId' => $projectDeletedEvent->getProjectId()->toString()
        ]);

        // Additional side effects:
        // - Archive project data
        // - Send notifications to project members
        // - Clean up related resources
        // - Update external systems
    }

    private function handleProjectWorkerAdded(ProjectWorkerAddedEvent $projectWorkerAddedEvent): void
    {
        $this->logger->info('Project worker added - side effects processing', [
            'projectId' => $projectWorkerAddedEvent->getProjectId()->toString(),
            'userId' => $projectWorkerAddedEvent->getUserId()->toString(),
            'role' => (string)$projectWorkerAddedEvent->getRole()
        ]);

        // Additional side effects:
        // - Send notification to new worker
        // - Update access permissions
        // - Send notification to project owner
    }

    private function handleProjectWorkerRemoved(ProjectWorkerRemovedEvent $projectWorkerRemovedEvent): void
    {
        $this->logger->info('Project worker removed - side effects processing', [
            'projectId' => $projectWorkerRemovedEvent->getProjectId()->toString(),
            'userId' => $projectWorkerRemovedEvent->getUserId()->toString()
        ]);

        // Additional side effects:
        // - Revoke access permissions
        // - Send notification to removed worker
        // - Send notification to project owner
    }
}
