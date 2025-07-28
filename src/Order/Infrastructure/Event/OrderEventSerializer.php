<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Event;

use App\Order\Domain\Event\ItemAddedEvent;
use App\Order\Domain\Event\ItemRemovedEvent;
use App\Order\Domain\Event\OrderCreatedEvent;
use App\Order\Domain\Event\OrderStatusChangedEvent;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Event\EventSerializer;

final readonly class OrderEventSerializer implements EventSerializer
{
    private const SUPPORTED_EVENTS = [
        OrderCreatedEvent::class,
        ItemAddedEvent::class,
        ItemRemovedEvent::class,
        OrderStatusChangedEvent::class,
    ];

    public function supports(string $eventType): bool
    {
        return in_array($eventType, self::SUPPORTED_EVENTS, true);
    }

    public function serialize(DomainEvent $event): string
    {
        return json_encode($event->getEventData(), JSON_THROW_ON_ERROR);
    }

    public function deserialize(string $eventData, string $eventType): DomainEvent
    {
        $data = json_decode($eventData, true, 512, JSON_THROW_ON_ERROR);

        return match ($eventType) {
            OrderCreatedEvent::class => OrderCreatedEvent::fromEventData($data),
            ItemAddedEvent::class => ItemAddedEvent::fromEventData($data),
            ItemRemovedEvent::class => ItemRemovedEvent::fromEventData($data),
            OrderStatusChangedEvent::class => OrderStatusChangedEvent::fromEventData($data),
            default => throw new \InvalidArgumentException("Unknown event type: {$eventType}")
        };
    }
}