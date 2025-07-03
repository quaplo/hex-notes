<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model;

use App\Shared\Domain\Event\DomainEvent;

abstract class AggregateRoot
{
    /** @var DomainEvent[] */
    private array $domainEvents = [];
    private int $version = 0;

    protected function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return DomainEvent[]
     */
    public function getUncommittedEvents(): array
    {
        return $this->domainEvents;
    }

    public function markEventsAsCommitted(): void
    {
        $this->domainEvents = [];
    }

    public function hasUncommittedEvents(): bool
    {
        return count($this->domainEvents) > 0;
    }

    /**
     * @return DomainEvent[]
     */
    public function getDomainEvents(): array
    {
        return $this->domainEvents;
    }

    public function clearDomainEvents(): void
    {
        $this->domainEvents = [];
    }

    public function replayEvent(DomainEvent $event): void
    {
        $this->handleEvent($event);
        $this->version++;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): void
    {
        $this->version = $version;
    }

    /**
     * Handle domain event for Event Sourcing replay
     */
    protected abstract function handleEvent(DomainEvent $event): void;
}