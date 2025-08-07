<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Event\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class DomainEventDispatcher implements EventDispatcher
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @param DomainEvent[] $events
     */
    public function dispatch(array $events): void
    {
        foreach ($events as $event) {
            $this->eventDispatcher->dispatch($event, $event::class);
        }
    }
}
