<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;

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
     * Apply event to aggregate state and record it
     */
    protected function apply(DomainEvent $event): void
    {
        $this->handleEvent($event);
        $this->recordEvent($event);
    }

    /**
     * @return DomainEvent[]
     */
    public function getUncommittedEvents(): array
    {
        return $this->domainEvents;
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

    public function markEventsAsCommitted(): void
    {
        $this->domainEvents = [];
    }

    public function hasUncommittedEvents(): bool
    {
        return count($this->domainEvents) > 0;
    }

    /**
     * Public method for replaying events during aggregate reconstruction
     */
    public function replayEvent(DomainEvent $event): void
    {
        $this->handleEvent($event);
        $this->version++;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    protected function setVersion(int $version): void
    {
        $this->version = $version;
    }

    /**
     * Get the unique identifier of this aggregate
     */
    abstract public function getId(): Uuid;

    /**
     * Handle domain event for Event Sourcing replay and state changes
     */
    abstract protected function handleEvent(DomainEvent $event): void;
}