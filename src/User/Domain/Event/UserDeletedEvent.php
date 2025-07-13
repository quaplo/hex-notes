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
        private Uuid $uuid,
        private Email $email,
        private DateTimeImmutable $occurredAt
    ) {
    }

    public static function create(Uuid $uuid, Email $email): self
    {
        return new self(
            $uuid,
            $email,
            new DateTimeImmutable()
        );
    }

    public function getUserId(): Uuid
    {
        return $this->uuid;
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
