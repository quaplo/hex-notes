<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity]
#[ORM\Table(name: 'user_project')]
class ProjectWorkerEntity
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: ProjectEntity::class)]
    #[ORM\JoinColumn(name: 'project_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ProjectEntity $project;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private UserEntity $user;

    #[ORM\Column(length: 50)]
    private string $role;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: 'added_by', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private UserEntity $addedBy;

    public function __construct(
        ProjectEntity $project,
        UserEntity $user,
        string $role,
        UserEntity $addedBy,
        ?DateTimeImmutable $createdAt = null
    ) {
        $this->project = $project;
        $this->user = $user;
        $this->role = $role;
        $this->addedBy = $addedBy;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function getProject(): ProjectEntity
    {
        return $this->project;
    }

    public function getUser(): UserEntity
    {
        return $this->user;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getAddedBy(): UserEntity
    {
        return $this->addedBy;
    }
}
