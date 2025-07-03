<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;
use App\Shared\ValueObject\Email;
use DateTimeImmutable;

final readonly class UserDeletedEvent implements DomainEvent
{
    public function __construct(
        private Uuid $userId,
        private Email $email,
        private DateTimeImmutable $occurredAt
    ) {
    }

    public static function create(Uuid $userId, Email $email): self
    {
        return new self(
            $userId,
            $email,
            new DateTimeImmutable()
        );
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}