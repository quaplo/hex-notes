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
        private ProjectReadModelProjection $readModelProjection
    ) {
    }

    public function handle(DomainEvent $event): void
    {
        // First update read model projection
        $this->readModelProjection->handle($event);

        // Then handle other side effects
        match (get_class($event)) {
            ProjectCreatedEvent::class => $this->handleProjectCreated($event),
            ProjectRenamedEvent::class => $this->handleProjectRenamed($event),
            ProjectDeletedEvent::class => $this->handleProjectDeleted($event),
            ProjectWorkerAddedEvent::class => $this->handleProjectWorkerAdded($event),
            ProjectWorkerRemovedEvent::class => $this->handleProjectWorkerRemoved($event),
            default => $this->logger->info('Unhandled project event', ['event' => get_class($event)])
        };
    }

    private function handleProjectCreated(ProjectCreatedEvent $event): void
    {
        $this->logger->info('Project created - side effects processing', [
            'projectId' => $event->getProjectId()->toString(),
            'name' => $event->getName()->__toString()
        ]);

        // Additional side effects:
        // - Send welcome email to project owner
        // - Create audit log entry
        // - Send notifications
        // - Update external systems
    }

    private function handleProjectRenamed(ProjectRenamedEvent $event): void
    {
        $this->logger->info('Project renamed - side effects processing', [
            'projectId' => $event->getProjectId()->toString(),
            'oldName' => $event->getOldName()->__toString(),
            'newName' => $event->getNewName()->__toString()
        ]);

        // Additional side effects:
        // - Send notifications to project members
        // - Update search index
        // - Update external integrations
    }

    private function handleProjectDeleted(ProjectDeletedEvent $event): void
    {
        $this->logger->info('Project deleted - side effects processing', [
            'projectId' => $event->getProjectId()->toString()
        ]);

        // Additional side effects:
        // - Archive project data
        // - Send notifications to project members
        // - Clean up related resources
        // - Update external systems
    }

    private function handleProjectWorkerAdded(ProjectWorkerAddedEvent $event): void
    {
        $this->logger->info('Project worker added - side effects processing', [
            'projectId' => $event->getProjectId()->toString(),
            'userId' => $event->getUserId()->toString(),
            'role' => (string)$event->getRole()
        ]);

        // Additional side effects:
        // - Send notification to new worker
        // - Update access permissions
        // - Send notification to project owner
    }

    private function handleProjectWorkerRemoved(ProjectWorkerRemovedEvent $event): void
    {
        $this->logger->info('Project worker removed - side effects processing', [
            'projectId' => $event->getProjectId()->toString(),
            'userId' => $event->getUserId()->toString()
        ]);

        // Additional side effects:
        // - Revoke access permissions
        // - Send notification to removed worker
        // - Send notification to project owner
    }
}
