<?php

// src/Infrastructure/Persistence/Doctrine/Entity/UserEntity.php
namespace App\User\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class UserEntity
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'uuid')]
        #[ORM\GeneratedValue(strategy: 'NONE')]
        private string $id,
        #[ORM\Column(length: 255, unique: true)]
        private string $email,
        #[ORM\Column(length: 20)]
        private string $status,
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
        private DateTimeImmutable $createdAt,
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
        private ?DateTimeImmutable $deletedAt = null
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
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
}
