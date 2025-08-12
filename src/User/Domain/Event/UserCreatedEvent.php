<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\ValueObject\Email;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final readonly class UserCreatedEvent implements DomainEvent
{
    public function __construct(
        private Uuid $userId,
        private Email $email,
        private DateTimeImmutable $createdAt,
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

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getEventName(): string
    {
        return 'user.created';
    }

    public function getEventData(): array
    {
        return [
            'userId' => $this->userId->toString(),
            'email' => $this->email->__toString(),
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
