<?php

declare(strict_types=1);

namespace App\Shared\Event;

use App\Shared\Domain\Event\DomainEvent;

interface EventDispatcher
{
    /**
     * @param DomainEvent[] $events
     */
    public function dispatch(array $events): void;
}
