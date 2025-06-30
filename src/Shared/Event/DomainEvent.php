<?php

declare(strict_types=1);

namespace App\Shared\Event;

use DateTimeImmutable;

interface DomainEvent
{
    public function getOccurredAt(): DateTimeImmutable;
}
