<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Event;

use RuntimeException;
use DateTimeInterface;
use DateTimeImmutable;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Event\EventSerializer;
use App\Shared\ValueObject\Email;
use App\Shared\ValueObject\Uuid;
use App\User\Domain\Event\UserCreatedEvent;
use App\User\Domain\Event\UserDeletedEvent;
use JsonException;

final class UserEventSerializer implements EventSerializer
{
    private const SUPPORTED_EVENTS = [
        UserCreatedEvent::class,
        UserDeletedEvent::class,
    ];

    public function supports(string $eventType): bool
    {
        return in_array($eventType, self::SUPPORTED_EVENTS, true);
    }

    public function serialize(DomainEvent $domainEvent): string
    {
        try {
            return match ($domainEvent::class) {
                UserCreatedEvent::class => $this->serializeUserCreatedEvent($domainEvent),
                UserDeletedEvent::class => $this->serializeUserDeletedEvent($domainEvent),
                default => throw new RuntimeException("Unsupported event type for serialization: " . $domainEvent::class)
            };
        } catch (JsonException $e) {
            throw new RuntimeException('Failed to serialize event', 0, $e);
        }
    }

    public function deserialize(string $eventData, string $eventType): DomainEvent
    {
        try {
            $data = json_decode($eventData, true, 512, JSON_THROW_ON_ERROR);

            return match ($eventType) {
                UserCreatedEvent::class => $this->deserializeUserCreatedEvent($data),
                UserDeletedEvent::class => $this->deserializeUserDeletedEvent($data),
                default => throw new RuntimeException("Unsupported event type for deserialization: $eventType")
            };
        } catch (JsonException $e) {
            throw new RuntimeException('Failed to deserialize event', 0, $e);
        }
    }

    private function serializeUserCreatedEvent(DomainEvent $domainEvent): string
    {
        $data = [
            'userId' => $domainEvent->userId->toString(),
            'email' => $domainEvent->email->__toString(),
            'createdAt' => $domainEvent->createdAt->format(DateTimeInterface::ATOM)
        ];
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function serializeUserDeletedEvent(UserDeletedEvent $userDeletedEvent): string
    {
        $data = [
            'userId' => $userDeletedEvent->getUserId()->toString(),
            'email' => $userDeletedEvent->getEmail()->__toString(),
            'occurredAt' => $userDeletedEvent->getOccurredAt()->format(DateTimeInterface::ATOM)
        ];
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function deserializeUserCreatedEvent(array $data): UserCreatedEvent
    {
        return new UserCreatedEvent(
            Uuid::create($data['userId']),
            new Email($data['email']),
            new DateTimeImmutable($data['createdAt'])
        );
    }

    private function deserializeUserDeletedEvent(array $data): UserDeletedEvent
    {
        return UserDeletedEvent::create(
            Uuid::create($data['userId']),
            Email::fromString($data['email'])
        );
    }
}