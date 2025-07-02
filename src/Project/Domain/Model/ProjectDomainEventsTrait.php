<?php

declare(strict_types=1);

namespace App\Project\Domain\Model;

use App\Project\Domain\Event\ProjectCreatedEvent;
use App\Project\Domain\Event\ProjectDeletedEvent;
use App\Project\Domain\Event\ProjectRenamedEvent;
use App\Project\Domain\Event\ProjectWorkerAddedEvent;
use App\Project\Domain\Event\ProjectWorkerRemovedEvent;
use App\Project\Domain\ValueObject\ProjectName;
use App\Project\Domain\ValueObject\ProjectWorker;
use App\Shared\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

trait ProjectDomainEventsTrait
{
    protected function recordProjectCreated(ProjectName $name, Uuid $ownerId): void
    {
        $this->recordEvent(new ProjectCreatedEvent($this->id, $name, $ownerId));
    }

    protected function recordProjectRenamed(ProjectName $oldName, ProjectName $newName): void
    {
        $this->recordEvent(new ProjectRenamedEvent($this->id, $oldName, $newName));
    }

    protected function recordProjectDeleted(): void
    {
        $this->recordEvent(new ProjectDeletedEvent($this->id));
    }

    protected function handleEvent(DomainEvent $event): void
    {
        match (get_class($event)) {
            ProjectCreatedEvent::class => $this->handleProjectCreated($event),
            ProjectRenamedEvent::class => $this->handleProjectRenamed($event),
            ProjectDeletedEvent::class => $this->handleProjectDeleted($event),
            ProjectWorkerAddedEvent::class => $this->handleProjectWorkerAdded($event),
            ProjectWorkerRemovedEvent::class => $this->handleProjectWorkerRemoved($event),
            default => throw new \RuntimeException('Unknown event type: ' . get_class($event))
        };
    }

    private function handleProjectCreated(ProjectCreatedEvent $event): void
    {
        $this->id = $event->getProjectId();
        $this->name = $event->getName();
        $this->createdAt = $event->getOccurredAt();
        $this->ownerId = $event->getOwnerId();
    }

    private function handleProjectRenamed(ProjectRenamedEvent $event): void
    {
        $this->name = $event->getNewName();
    }

    private function handleProjectDeleted(ProjectDeletedEvent $event): void
    {
        $this->deletedAt = new DateTimeImmutable();
    }

    private function handleProjectWorkerAdded(ProjectWorkerAddedEvent $event): void
    {
        $this->workers[] = ProjectWorker::create(
            $event->getUserId(),
            $event->getRole(),
            $event->getAddedBy(),
            $event->getOccurredAt()
        );
    }

    private function handleProjectWorkerRemoved(ProjectWorkerRemovedEvent $event): void
    {
        $this->workers = array_filter(
            $this->workers,
            fn($worker) => !$worker->getUserId()->equals($event->getUserId())
        );
    }
}