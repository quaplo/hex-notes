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

    protected function recordEvent(DomainEvent $domainEvent): void
    {
        $this->domainEvents[] = $domainEvent;
    }

    /**
     * Apply event to aggregate state and record it
     */
    protected function apply(DomainEvent $domainEvent): void
    {
        $this->handleEvent($domainEvent);
        $this->recordEvent($domainEvent);
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
    public function replayEvent(DomainEvent $domainEvent): void
    {
        $this->handleEvent($domainEvent);
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
     * Set version for snapshot restoration (package-private)
     */
    public function restoreVersion(int $version): void
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
    abstract protected function handleEvent(DomainEvent $domainEvent): void;
}