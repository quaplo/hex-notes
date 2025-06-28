<?php

declare(strict_types=1);

namespace App\Domain\Project\Model;

use App\Shared\ValueObject\Uuid;
use App\Domain\Project\ValueObject\ProjectName;
use DateTimeImmutable;

final class Project
{
    /**
     * @var ProjectWorker[]
     */
    private array $workers = [];

    public static function create(ProjectName $name): self
    {
        return new self(Uuid::generate(), $name, new DateTimeImmutable(), null);
    }

    public function __construct(
        private Uuid $id,
        private ProjectName $name,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $deletedAt = null,
    ) {
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
        return new self($this->id, $this->name, $this->createdAt, new DateTimeImmutable());
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
