<?php

declare(strict_types=1);

namespace App\Project\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

abstract readonly class ProjectEvent implements DomainEvent
{
    protected function __construct(
        protected Uuid $projectId,
        protected DateTimeImmutable $occurredAt = new DateTimeImmutable(),
    ) {
    }

    public function getProjectId(): Uuid
    {
        return $this->projectId;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getAggregateId(): string
    {
        return $this->projectId->toString();
    }

    abstract public function getEventName(): string;

    /**
     * Child classes must implement this method to provide their specific event data.
     * They should merge getBaseEventData() with their own data.
     */
    abstract public function getEventData(): array;

    /**
     * Child classes must implement this static method for event deserialization.
     */
    abstract public static function fromEventData(array $eventData): self;

    /**
     * Returns the base event data that is common to all project events.
     * Child classes should merge this with their specific data.
     */
    protected function getBaseEventData(): array
    {
        return [
            'projectId' => $this->projectId->toString(),
            'occurredAt' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }
}
