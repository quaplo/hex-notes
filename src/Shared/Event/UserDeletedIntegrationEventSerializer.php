<?php

declare(strict_types=1);

namespace App\Shared\Event;

use InvalidArgumentException;
use DateTimeInterface;
use App\Shared\Domain\Event\UserDeletedIntegrationEvent;
use App\Shared\Domain\Event\DomainEvent;

final class UserDeletedIntegrationEventSerializer implements EventSerializer
{
    public function serialize(DomainEvent $domainEvent): string
    {
        if (!$domainEvent instanceof UserDeletedIntegrationEvent) {
            throw new InvalidArgumentException(
                sprintf('Expected %s, got %s', UserDeletedIntegrationEvent::class, $domainEvent::class)
            );
        }

        $data = [
            'userId' => $domainEvent->getUserId()->toString(),
            'userEmail' => $domainEvent->getUserEmail(),
            'occurredAt' => $domainEvent->getOccurredAt()->format(DateTimeInterface::ATOM)
        ];
        
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    public function deserialize(string $eventData, string $eventType): DomainEvent
    {
        $data = json_decode($eventData, true, 512, JSON_THROW_ON_ERROR);
        
        return UserDeletedIntegrationEvent::fromArray($data);
    }

    public function supports(string $eventType): bool
    {
        return $eventType === UserDeletedIntegrationEvent::class;
    }
}