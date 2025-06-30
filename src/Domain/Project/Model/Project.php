<?php

declare(strict_types=1);

namespace App\Domain\Project\Model;

use App\Domain\Project\Event\ProjectCreatedEvent;
use App\Domain\Project\Event\ProjectRenamedEvent;
use App\Domain\Project\Event\ProjectDeletedEvent;
use App\Domain\Project\ValueObject\ProjectName;
use App\Domain\Project\ValueObject\ProjectOwner;
use App\Domain\Project\ValueObject\UserId;
use App\Shared\Aggregate\AggregateRoot;
use App\Shared\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final class Project extends AggregateRoot
{
    /**
     * @var ProjectWorker[]
     */
    private array $workers = [];

    private UserId $createdBy;

    public function getOwner(): ProjectOwner
    {
        return $this->owner;
    }

    public static function create(ProjectName $name, ProjectOwner $owner): self
    {
        $project = new self(Uuid::generate(), $name, new DateTimeImmutable(), $owner);
        $project->recordProjectCreated($name, $owner);
        return $project;
    }

    public function __construct(
        private Uuid $id,
        private ProjectName $name,
        private DateTimeImmutable $createdAt,
        private ProjectOwner $owner,
        private ?DateTimeImmutable $deletedAt = null,
    ) {
        $this->createdBy = $this->owner->getId();
    }

    public function getCreatedBy(): UserId
    {
        return $this->createdBy;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): ProjectName
    {
        return $this->name;
    }

    public function rename(ProjectName $name): self
    {
        if ($this->isDeleted()) {
            throw new \DomainException('Cannot rename deleted project');
        }

        $project = new self(
            $this->id,
            $name,
            $this->createdAt,
            $this->owner,
            $this->deletedAt
        );
        
        $project->workers = $this->workers;
        $project->createdBy = $this->createdBy;
        $project->setVersion($this->getVersion());
        
        $project->recordProjectRenamed($this->name, $name);
        
        return $project;
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
        
        $project = new self(
            $this->id,
            $this->name,
            $this->createdAt,
            $this->owner,
            new DateTimeImmutable()
        );
        
        $project->workers = $this->workers;
        $project->createdBy = $this->createdBy;
        $project->setVersion($this->getVersion());
        
        $project->recordProjectDeleted();
        
        return $project;
    }

    /** @return ProjectWorker[] */
    public function getWorkers(): array
    {
        return $this->workers;
    }

    public function getOwnerEmail()
    {
        return $this->owner->getEmail();
    }

    public function addWorker(ProjectWorker $worker): void
    {
        if ($this->isDeleted()) {
            throw new \DomainException('Cannot add worker to deleted project');
        }

        foreach ($this->workers as $existing) {
            if ($existing->getUserId()->equals($worker->getUserId())) {
                return;
            }
        }

        $this->workers[] = $worker;
    }

    protected function recordProjectCreated(ProjectName $name, ProjectOwner $owner): void
    {
        $this->recordEvent(new ProjectCreatedEvent($this->id, $name, $owner));
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
            default => throw new \RuntimeException('Unknown event type: ' . get_class($event))
        };
    }

    private function handleProjectCreated(ProjectCreatedEvent $event): void
    {
        // Pri event replay nastavíme stav aggregate z eventu
        $this->id = $event->getProjectId();
        $this->name = $event->getName();
        $this->owner = $event->getOwner();
        $this->createdAt = $event->getOccurredAt();
        $this->createdBy = $this->owner->getId();
    }

    private function handleProjectRenamed(ProjectRenamedEvent $event): void
    {
        $this->name = $event->getNewName();
    }

    private function handleProjectDeleted(ProjectDeletedEvent $event): void
    {
        $this->deletedAt = new DateTimeImmutable();
    }
}
