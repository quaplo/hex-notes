<?php

declare(strict_types=1);

namespace App\Shared\Aggregate;

use App\Shared\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;

abstract class AggregateRoot
{
    /**
     * @var DomainEvent[]
     */
    private array $domainEvents = [];
    
    private int $version = 0;

    protected function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
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

    public function getVersion(): int
    {
        return $this->version;
    }

    protected function setVersion(int $version): void
    {
        $this->version = $version;
    }

    abstract public function getId(): Uuid;

    /**
     * Apply event to aggregate state
     */
    protected function apply(DomainEvent $event): void
    {
        $this->handleEvent($event);
        $this->version++;
    }

    /**
     * Public method for replaying events during aggregate reconstruction
     */
    public function replayEvent(DomainEvent $event): void
    {
        $this->apply($event);
    }

    /**
     * Handle specific event type
     */
    abstract protected function handleEvent(DomainEvent $event): void;
} 