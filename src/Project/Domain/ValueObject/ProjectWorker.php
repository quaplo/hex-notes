<?php

declare(strict_types=1);

namespace App\Project\Domain\ValueObject;

use App\Project\Domain\ValueObject\ProjectRole;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final readonly class ProjectWorker
{
    public function __construct(
        private Uuid $userId,
        private ProjectRole $projectRole,
        private DateTimeImmutable $createdAt,
        private Uuid $addedBy,
    ) {
    }

    public static function create(
        Uuid $userId,
        ProjectRole $projectRole,
        Uuid $addedBy,
        ?DateTimeImmutable $createdAt = null
    ): self {
        return new self(
            $userId,
            $projectRole,
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
        return $this->projectRole;
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
            && $this->projectRole->equals($other->projectRole)
            && $this->createdAt == $other->createdAt
            && $this->addedBy->equals($other->addedBy);
    }

}