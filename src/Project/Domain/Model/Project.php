<?php

declare(strict_types=1);

namespace App\Project\Domain\Model;

use App\Project\Domain\Event\ProjectWorkerAddedEvent;
use App\Project\Domain\Event\ProjectWorkerRemovedEvent;
use App\Project\Domain\ValueObject\ProjectName;
use App\Project\Domain\ValueObject\ProjectWorker;
use App\Shared\Aggregate\AggregateRoot;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final class Project extends AggregateRoot
{
    use ProjectDomainEventsTrait;

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
        $project->recordProjectCreated($name, $ownerId);
        return $project;
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

        $project = new self(
            $this->id,
            $this->name,
            $this->createdAt,
            $this->ownerId,
            new DateTimeImmutable()
        );

        $project->workers = $this->workers;
        $project->setVersion($this->getVersion());

        $project->recordProjectDeleted();

        return $project;
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

        $project = clone $this;
        $project->workers[] = $worker;
        $project->setVersion($this->getVersion());
        $project->recordEvent(new ProjectWorkerAddedEvent(
            $this->id,
            $worker->getUserId(),
            $worker->getRole(),
            $worker->getAddedBy()
        ));
        return $project;
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

        $project = clone $this;
        $project->workers = array_filter(
            $this->workers,
            fn($worker) => !$worker->getUserId()->equals($userId)
        );
        $project->setVersion($this->getVersion());
        $project->recordEvent(new ProjectWorkerRemovedEvent(
            $this->id,
            $userId,
            $removedBy
        ));
        return $project;
    }
}
