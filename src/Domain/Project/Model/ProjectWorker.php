<?php

declare(strict_types=1);

namespace App\Domain\Project\Model;

use App\Domain\Project\ValueObject\ProjectRole;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final class ProjectWorker
{
    public function __construct(
        private Uuid $userId,
        private ProjectRole $role,
        private DateTimeImmutable $createdAt,
        private ?Uuid $addedBy = null,
    ) {
    }

    public static function create(
        Uuid $userId,
        ProjectRole $role,
        ?Uuid $addedBy = null,
        ?DateTimeImmutable $createdAt = null
    ): self {
        return new self(
            $userId,
            $role,
            $createdAt ?? new DateTimeImmutable(),
            $addedBy
        );
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }

    public function getRole(): ProjectRole
    {
        return $this->role;
    }

    public function setRole(ProjectRole $role): void
    {
        $this->role = $role;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getAddedBy(): ?Uuid
    {
        return $this->addedBy;
    }
}
