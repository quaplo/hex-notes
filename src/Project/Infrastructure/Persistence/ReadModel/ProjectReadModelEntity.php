<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Persistence\ReadModel;

use Doctrine\DBAL\Types\Types;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'project_read_model')]
#[ORM\Index(columns: ['owner_id'], name: 'idx_project_owner')]
#[ORM\Index(columns: ['deleted_at'], name: 'idx_project_deleted')]
class ProjectReadModelEntity
{
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'deleted_at', nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

    #[ORM\Column(type: Types::JSON, name: 'workers')]
    private array $workers = [];

    #[ORM\Column(type: Types::INTEGER, name: 'version')]
    private int $version = 0;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: Types::STRING, length: 36)]
        private string $id,
        #[ORM\Column(type: Types::STRING, length: 255)]
        private string $name,
        #[ORM\Column(type: Types::STRING, length: 36, name: 'owner_id')]
        private string $ownerId,
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
        private DateTimeImmutable $createdAt
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getOwnerId(): string
    {
        return $this->ownerId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?DateTimeImmutable $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt instanceof DateTimeImmutable;
    }

    public function getWorkers(): array
    {
        return $this->workers;
    }

    public function setWorkers(array $workers): void
    {
        $this->workers = $workers;
    }

    public function addWorker(array $worker): void
    {
        $this->workers[] = $worker;
    }

    public function removeWorker(string $userId): void
    {
        $this->workers = array_filter(
            $this->workers,
            fn(array $worker): bool => $worker['userId'] !== $userId
        );
        $this->workers = array_values($this->workers); // Reindex
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): void
    {
        $this->version = $version;
    }

    public function incrementVersion(): void
    {
        $this->version++;
    }
}
