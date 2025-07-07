<?php

declare(strict_types=1);

namespace App\Infrastructure\Event;

use RuntimeException;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Event\EventSerializer;

final readonly class CompositeEventSerializer implements EventSerializer
{
    /**
     * @var EventSerializer[]
     */
    private array $serializers;

    public function __construct(EventSerializer ...$serializers)
    {
        $this->serializers = $serializers;
    }

    public function serialize(DomainEvent $domainEvent): string
    {
        $eventType = $domainEvent::class;
        
        foreach ($this->serializers as $serializer) {
            if ($serializer->supports($eventType)) {
                return $serializer->serialize($domainEvent);
            }
        }

        throw new RuntimeException("No serializer found for event type: $eventType");
    }

    public function deserialize(string $eventData, string $eventType): DomainEvent
    {
        foreach ($this->serializers as $serializer) {
            if ($serializer->supports($eventType)) {
                return $serializer->deserialize($eventData, $eventType);
            }
        }

        throw new RuntimeException("No serializer found for event type: $eventType");
    }

    public function supports(string $eventType): bool
    {
        foreach ($this->serializers as $serializer) {
            if ($serializer->supports($eventType)) {
                return true;
            }
        }

        return false;
    }
}