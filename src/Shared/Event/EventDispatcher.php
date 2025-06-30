<?php

declare(strict_types=1);

namespace App\Shared\Event;

interface EventDispatcher
{
    /**
     * @param DomainEvent[] $events
     */
    public function dispatch(array $events): void;
}
