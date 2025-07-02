<?php

declare(strict_types=1);

namespace App\Project\Domain\ValueObject;

use App\Project\Domain\ValueObject\ProjectRole;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final class ProjectWorker
{
    public function __construct(
        private readonly Uuid $userId,
        private readonly ProjectRole $role,
        private readonly DateTimeImmutable $createdAt,
        private readonly Uuid $addedBy,
    ) {
    }

    public static function create(
        Uuid $userId,
        ProjectRole $role,
        Uuid $addedBy,
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

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getAddedBy(): Uuid
    {
        return $this->addedBy;
    }

    public function equals(self $other): bool
    {
        return $this->userId->equals($other->userId)
            && $this->role->equals($other->role)
            && $this->createdAt == $other->createdAt
            && $this->addedBy->equals($other->addedBy);
    }

    public function withRole(ProjectRole $role): self
    {
        return new self(
            $this->userId,
            $role,
            $this->createdAt,
            $this->addedBy
        );
    }
}