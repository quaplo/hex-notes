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
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final class Project extends AggregateRoot
{

    /**
     * @var ProjectWorker[]
     */
    private array $workers = [];
    private Uuid $ownerId;

    public function __construct(
        private Uuid $id,
        private ProjectName $name,
        private DateTimeImmutable $createdAt,
        Uuid $ownerId,
        private ?DateTimeImmutable $deletedAt = null,
    ) {
        $this->ownerId = $ownerId;
    }

    public static function create(ProjectName $name, Uuid $ownerId): self
    {
        $project = new self(Uuid::generate(), $name, new DateTimeImmutable(), $ownerId);
        $project->apply(new ProjectCreatedEvent($project->getId(), $name, $ownerId));
        return $project;
    }

    /**
     * Creates an empty Project aggregate for Event Sourcing replay.
     * All properties will be set by replaying domain events.
     */
    public static function createEmpty(): self
    {
        return new self(
            Uuid::create('00000000-0000-0000-0000-000000000000'), // Null UUID
            new ProjectName('__EMPTY__'),     // Placeholder name - will be set by ProjectCreatedEvent
            new DateTimeImmutable('1970-01-01T00:00:00+00:00'), // Epoch time
            Uuid::create('00000000-0000-0000-0000-000000000000')  // Null owner ID
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): ProjectName
    {
        return $this->name;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function delete(): self
    {
        if ($this->isDeleted()) {
            throw new \DomainException('Project is already deleted');
        }

        $this->apply(new ProjectDeletedEvent($this->getId()));

        return $this;
    }

    public function rename(ProjectName $newName): self
    {
        if ($this->isDeleted()) {
            throw new \DomainException('Cannot rename deleted project');
        }

        $oldName = $this->name;
        $this->apply(new ProjectRenamedEvent($this->getId(), $oldName, $newName));

        return $this;
    }

    /** @return ProjectWorker[] */
    public function getWorkers(): array
    {
        return $this->workers;
    }

    public function addWorker(ProjectWorker $worker): self
    {
        if ($this->isDeleted()) {
            throw new \DomainException('Cannot add worker to deleted project');
        }

        foreach ($this->workers as $existing) {
            if ($existing->getUserId()->equals($worker->getUserId())) {
                return $this;
            }
        }

        $this->apply(new ProjectWorkerAddedEvent(
            $this->id,
            $worker->getUserId(),
            $worker->getRole(),
            $worker->getAddedBy()
        ));
        return $this;
    }

    public function getOwnerId(): Uuid
    {
        return $this->ownerId;
    }

    public function removeWorkerByUserId(Uuid $userId, ?Uuid $removedBy = null): self
    {
        if ($this->isDeleted()) {
            throw new \DomainException('Cannot remove worker from deleted project');
        }

        $found = false;
        foreach ($this->workers as $worker) {
            if ($worker->getUserId()->equals($userId)) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            throw new \DomainException('Worker not found in project');
        }

        $this->apply(new ProjectWorkerRemovedEvent(
            $this->id,
            $userId,
            $removedBy
        ));
        return $this;
    }

    /**
     * Add worker directly without domain events (for snapshot restoration)
     */
    public function restoreWorker(ProjectWorker $worker): void
    {
        $this->workers[] = $worker;
    }

    /**
     * Implementation of abstract handleEvent method from AggregateRoot
     */
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
        $this->deletedAt = $event->getOccurredAt();
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
