<?php

declare(strict_types=1);

namespace App\Project\Domain\Model;

use DomainException;
use RuntimeException;
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

    public function __construct(
        private Uuid $id,
        private ProjectName $projectName,
        private DateTimeImmutable $createdAt,
        private Uuid $ownerId,
        private ?DateTimeImmutable $deletedAt = null
    ) {
    }

    public static function create(ProjectName $projectName, Uuid $uuid): self
    {
        $project = new self(Uuid::generate(), $projectName, new DateTimeImmutable(), $uuid);
        $project->apply(new ProjectCreatedEvent($project->getId(), $projectName, $uuid));
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
        return $this->projectName;
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
        return $this->deletedAt instanceof DateTimeImmutable;
    }

    public function delete(): self
    {
        if ($this->isDeleted()) {
            throw new DomainException('Project is already deleted');
        }

        $this->apply(new ProjectDeletedEvent($this->getId()));

        return $this;
    }

    public function rename(ProjectName $projectName): self
    {
        if ($this->isDeleted()) {
            throw new DomainException('Cannot rename deleted project');
        }

        $oldName = $this->projectName;
        $this->apply(new ProjectRenamedEvent($this->getId(), $oldName, $projectName));

        return $this;
    }

    /** @return ProjectWorker[] */
    public function getWorkers(): array
    {
        return $this->workers;
    }

    public function addWorker(ProjectWorker $projectWorker): self
    {
        if ($this->isDeleted()) {
            throw new DomainException('Cannot add worker to deleted project');
        }

        foreach ($this->workers as $worker) {
            if ($worker->getUserId()->equals($projectWorker->getUserId())) {
                return $this;
            }
        }

        $this->apply(new ProjectWorkerAddedEvent(
            $this->id,
            $projectWorker->getUserId(),
            $projectWorker->getRole(),
            $projectWorker->getAddedBy()
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
            throw new DomainException('Cannot remove worker from deleted project');
        }

        $found = false;
        foreach ($this->workers as $worker) {
            if ($worker->getUserId()->equals($userId)) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            throw new DomainException('Worker not found in project');
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
    public function restoreWorker(ProjectWorker $projectWorker): void
    {
        $this->workers[] = $projectWorker;
    }

    /**
     * Implementation of abstract handleEvent method from AggregateRoot
     */
    protected function handleEvent(DomainEvent $domainEvent): void
    {
        match ($domainEvent::class) {
            ProjectCreatedEvent::class => $this->handleProjectCreated($domainEvent),
            ProjectRenamedEvent::class => $this->handleProjectRenamed($domainEvent),
            ProjectDeletedEvent::class => $this->handleProjectDeleted($domainEvent),
            ProjectWorkerAddedEvent::class => $this->handleProjectWorkerAdded($domainEvent),
            ProjectWorkerRemovedEvent::class => $this->handleProjectWorkerRemoved($domainEvent),
            default => throw new RuntimeException('Unknown event type: ' . $domainEvent::class)
        };
    }

    private function handleProjectCreated(ProjectCreatedEvent $projectCreatedEvent): void
    {
        $this->id = $projectCreatedEvent->getProjectId();
        $this->projectName = $projectCreatedEvent->getName();
        $this->createdAt = $projectCreatedEvent->getOccurredAt();
        $this->ownerId = $projectCreatedEvent->getOwnerId();
    }

    private function handleProjectRenamed(ProjectRenamedEvent $projectRenamedEvent): void
    {
        $this->projectName = $projectRenamedEvent->getNewName();
    }

    private function handleProjectDeleted(ProjectDeletedEvent $projectDeletedEvent): void
    {
        $this->deletedAt = $projectDeletedEvent->getOccurredAt();
    }

    private function handleProjectWorkerAdded(ProjectWorkerAddedEvent $projectWorkerAddedEvent): void
    {
        $this->workers[] = ProjectWorker::create(
            $projectWorkerAddedEvent->getUserId(),
            $projectWorkerAddedEvent->getRole(),
            $projectWorkerAddedEvent->getAddedBy(),
            $projectWorkerAddedEvent->getOccurredAt()
        );
    }

    private function handleProjectWorkerRemoved(ProjectWorkerRemovedEvent $projectWorkerRemovedEvent): void
    {
        $this->workers = array_filter(
            $this->workers,
            fn($worker): bool => !$worker->getUserId()->equals($projectWorkerRemovedEvent->getUserId())
        );
    }
}
