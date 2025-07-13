<?php

declare(strict_types=1);

namespace App\Shared\Event;

use App\Shared\Domain\Event\DomainEvent;

interface EventSerializer
{
    public function serialize(DomainEvent $domainEvent): string;

    public function deserialize(string $eventData, string $eventType): DomainEvent;

    public function supports(string $eventType): bool;
}
