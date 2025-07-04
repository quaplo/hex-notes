<?php

declare(strict_types=1);

namespace App\Project\Domain\Model;

use App\Shared\Domain\Model\AggregateSnapshot;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final readonly class ProjectSnapshot implements AggregateSnapshot
{
    public function __construct(
        private Uuid $aggregateId,
        private int $version,
        private array $data,
        private DateTimeImmutable $createdAt
    ) {
    }

    public static function create(
        Uuid $aggregateId,
        int $version,
        array $projectData
    ): self {
        return new self(
            $aggregateId,
            $version,
            $projectData,
            new DateTimeImmutable()
        );
    }

    public function getAggregateId(): Uuid
    {
        return $this->aggregateId;
    }

    public function getAggregateType(): string
    {
        return 'Project';
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Helper method to get specific project data
     */
    public function getProjectId(): string
    {
        return $this->data['id'];
    }

    public function getProjectName(): string
    {
        return $this->data['name'];
    }

    public function getOwnerId(): string
    {
        return $this->data['ownerId'];
    }

    public function getProjectCreatedAt(): string
    {
        return $this->data['createdAt'];
    }

    public function getDeletedAt(): ?string
    {
        return $this->data['deletedAt'] ?? null;
    }

    public function getWorkers(): array
    {
        return $this->data['workers'] ?? [];
    }
}