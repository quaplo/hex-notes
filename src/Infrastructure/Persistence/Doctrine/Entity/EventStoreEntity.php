<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'event_store')]
#[ORM\Index(columns: ['aggregate_id', 'version'], name: 'idx_aggregate_version')]
#[ORM\Index(columns: ['occurred_at'], name: 'idx_occurred_at')]
class EventStoreEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $aggregateId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $eventType;

    #[ORM\Column(type: 'json')]
    private string $eventData;

    #[ORM\Column(type: 'integer')]
    private int $version;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $occurredAt;

    public function __construct(
        string $aggregateId,
        string $eventType,
        string $eventData,
        int $version,
        \DateTimeImmutable $occurredAt
    ) {
        $this->aggregateId = $aggregateId;
        $this->eventType = $eventType;
        $this->eventData = $eventData;
        $this->version = $version;
        $this->occurredAt = $occurredAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAggregateId(): string
    {
        return $this->aggregateId;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getEventData(): string
    {
        return $this->eventData;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
