<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use DateTimeImmutable;

interface DomainEvent
{
    public function getOccurredAt(): DateTimeImmutable;

    public function getEventName(): string;

    /**
     * @return array<string, mixed>
     */
    public function getEventData(): array;
}
