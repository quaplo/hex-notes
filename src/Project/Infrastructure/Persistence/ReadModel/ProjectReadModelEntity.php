<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Persistence\ReadModel;

use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'project_read_model')]
#[ORM\Index(columns: ['owner_id'], name: 'idx_project_owner')]
#[ORM\Index(columns: ['deleted_at'], name: 'idx_project_deleted')]
class ProjectReadModelEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 36, name: 'owner_id')]
    private string $ownerId;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', name: 'deleted_at', nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

    #[ORM\Column(type: 'json', name: 'workers')]
    private array $workers = [];

    #[ORM\Column(type: 'integer', name: 'version')]
    private int $version = 0;

    public function __construct(
        string $id,
        string $name,
        string $ownerId,
        DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->ownerId = $ownerId;
        $this->createdAt = $createdAt;
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
        return $this->deletedAt !== null;
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
            fn(array $worker) => $worker['userId'] !== $userId
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