<?php

declare(strict_types=1);

namespace App\Shared\Event;

use App\Shared\Domain\Event\UserDeletedIntegrationEvent;
use App\Shared\Domain\Event\DomainEvent;

final class UserDeletedIntegrationEventSerializer implements EventSerializer
{
    public function serialize(DomainEvent $event): string
    {
        if (!$event instanceof UserDeletedIntegrationEvent) {
            throw new \InvalidArgumentException(
                sprintf('Expected %s, got %s', UserDeletedIntegrationEvent::class, get_class($event))
            );
        }

        $data = [
            'userId' => $event->getUserId()->toString(),
            'userEmail' => $event->getUserEmail(),
            'occurredAt' => $event->getOccurredAt()->format(\DateTimeInterface::ATOM)
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