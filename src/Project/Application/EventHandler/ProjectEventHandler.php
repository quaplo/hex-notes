<?php

declare(strict_types=1);

namespace App\Project\Application\EventHandler;

use App\Project\Domain\Event\ProjectCreatedEvent;
use App\Project\Domain\Event\ProjectDeletedEvent;
use App\Project\Domain\Event\ProjectRenamedEvent;
use App\Shared\Event\DomainEvent;
use Psr\Log\LoggerInterface;

final class ProjectEventHandler
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function handle(DomainEvent $event): void
    {
        match (get_class($event)) {
            ProjectCreatedEvent::class => $this->handleProjectCreated($event),
            ProjectRenamedEvent::class => $this->handleProjectRenamed($event),
            ProjectDeletedEvent::class => $this->handleProjectDeleted($event),
            default => $this->logger->info('Unhandled project event', ['event' => get_class($event)])
        };
    }

    private function handleProjectCreated(ProjectCreatedEvent $event): void
    {
        $this->logger->info('Project created', [
            'projectId' => $event->getProjectId()->toString(),
            'name' => $event->getName()->__toString(),
            'ownerEmail' => $event->getOwner()->getEmail()->getValue(),
            'occurredAt' => $event->getOccurredAt()->format(\DateTimeInterface::ATOM)
        ]);

        // Here you could:
        // - Send welcome email to project owner
        // - Create audit log entry
        // - Update read models/projections
        // - Send notifications
    }

    private function handleProjectRenamed(ProjectRenamedEvent $event): void
    {
        $this->logger->info('Project renamed', [
            'projectId' => $event->getProjectId()->toString(),
            'oldName' => $event->getOldName()->__toString(),
            'newName' => $event->getNewName()->__toString(),
            'occurredAt' => $event->getOccurredAt()->format(\DateTimeInterface::ATOM)
        ]);

        // Here you could:
        // - Update read models
        // - Send notifications to project members
        // - Update search index
    }

    private function handleProjectDeleted(ProjectDeletedEvent $event): void
    {
        $this->logger->info('Project deleted', [
            'projectId' => $event->getProjectId()->toString(),
            'occurredAt' => $event->getOccurredAt()->format(\DateTimeInterface::ATOM)
        ]);

        // Here you could:
        // - Archive project data
        // - Send notifications to project members
        // - Update read models
        // - Clean up related resources
    }
} 