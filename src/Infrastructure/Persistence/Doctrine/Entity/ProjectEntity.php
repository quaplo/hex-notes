<?php

declare(strict_types=1);

// src/Infrastructure/Persistence/Doctrine/Entity/ProjectEntity.php
namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity]
#[ORM\Table(name: 'projects')]
class ProjectEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

    /**
     * @var Collection<int, UserEntity>
     */
    #[ORM\ManyToMany(targetEntity: UserEntity::class, inversedBy: 'projects')]
    #[ORM\JoinTable(name: 'user_project')]
    private Collection $users;

    public function __construct(
        string $id,
        string $name,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $deletedAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->createdAt = $createdAt;
        $this->users = new ArrayCollection();
        $this->deletedAt = $deletedAt;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
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

    public function setDeletedAt(?DateTimeImmutable $dateTime = null): self
    {
        $this->deletedAt = $dateTime ? $dateTime : new DateTimeImmutable();
        return $this;
    }

    public function softDelete(): void
    {
        $this->deletedAt = new DateTimeImmutable();
    }

    /**
     * @return Collection<int, UserEntity>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(UserEntity $user): void
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }
    }
}
