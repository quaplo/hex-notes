<?php

declare(strict_types=1);

namespace App\Tests\Project\Doubles;

use App\Shared\Event\DomainEvent;
use App\Shared\Event\EventDispatcher;

final class InMemoryEventDispatcher implements EventDispatcher
{
    /** @var array<DomainEvent> */
    private array $dispatchedEvents = [];

    public function dispatch(array $events): void
    {
        foreach ($events as $event) {
            $this->dispatchedEvents[] = $event;
        }
    }


    // Testing helper methods
    
    /**
     * @return array<DomainEvent>
     */
    public function getDispatchedEvents(): array
    {
        return $this->dispatchedEvents;
    }

    public function getDispatchedEventCount(): int
    {
        return count($this->dispatchedEvents);
    }

    public function getEventsByType(string $eventClass): array
    {
        return array_filter(
            $this->dispatchedEvents,
            fn(DomainEvent $domainEvent): bool => $domainEvent instanceof $eventClass
        );
    }

    public function hasEventOfType(string $eventClass): bool
    {
        foreach ($this->dispatchedEvents as $dispatchedEvent) {
            if ($dispatchedEvent instanceof $eventClass) {
                return true;
            }
        }
        return false;
    }

    public function clear(): void
    {
        $this->dispatchedEvents = [];
    }

    public function getLastDispatchedEvent(): ?DomainEvent
    {
        if ($this->dispatchedEvents === []) {
            return null;
        }
        
        return end($this->dispatchedEvents);
    }
}