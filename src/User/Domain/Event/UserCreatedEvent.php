<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Event\DomainEvent;
use App\Shared\ValueObject\Email;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final class UserCreatedEvent implements DomainEvent
{
    public function __construct(
        public readonly Uuid $userId,
        public readonly Email $email,
        public readonly DateTimeImmutable $createdAt
    ) {
    }

    public function getAggregateId(): string
    {
        return $this->userId->toString();
    }

    public function getEventName(): string
    {
        return 'user.created';
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getEventData(): array
    {
        return [
            'userId' => $this->userId->toString(),
            'email' => $this->email->__toString(),
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s')
        ];
    }

    public static function fromEventData(array $eventData): self
    {
        return new self(
            userId: Uuid::fromString($eventData['userId']),
            email: new Email($eventData['email']),
            createdAt: new DateTimeImmutable($eventData['createdAt'])
        );
    }
} 