<?php

declare(strict_types=1);

namespace App\Domain\Project\Model;

use App\Domain\Project\ValueObject\ProjectName;
use App\Domain\Project\ValueObject\ProjectOwner;
use App\Domain\Project\ValueObject\UserId;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final class Project
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
        return new self(Uuid::generate(), $name, new DateTimeImmutable(), $owner);
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
        $this->name = $name;
        return $this;
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
        return new self($this->id, $this->name, $this->createdAt, $this->owner, new DateTimeImmutable());
    }

    /** @return ProjectWorker[] */
    public function getWorkers(): array
    {
        return $this->workers;
    }

    public function addWorker(ProjectWorker $worker): void
    {
        foreach ($this->workers as $existing) {
            if ($existing->getUserId()->equals($worker->getUserId())) {
                return;
            }
        }

        $this->workers[] = $worker;
    }
}
