<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'event_store')]
#[ORM\Index(columns: ['aggregate_type', 'occurred_at'], name: 'idx_aggregate_type_occurred_at')]
#[ORM\Index(columns: ['occurred_at'], name: 'idx_occurred_at')]
#[ORM\Index(columns: ['aggregate_type'], name: 'idx_aggregate_type')]
class EventStoreEntity
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: Types::STRING, length: 36)]
        private string $aggregateId,
        #[ORM\Id]
        #[ORM\Column(type: Types::INTEGER)]
        private int $version,
        #[ORM\Column(type: Types::STRING, length: 255)]
        private string $aggregateType,
        #[ORM\Column(type: Types::STRING, length: 255)]
        private string $eventType,
        #[ORM\Column(type: Types::JSON)]
        private string $eventData,
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
        private DateTimeImmutable $occurredAt,
    ) {
    }

    public function getAggregateId(): string
    {
        return $this->aggregateId;
    }

    public function getAggregateType(): string
    {
        return $this->aggregateType;
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

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
