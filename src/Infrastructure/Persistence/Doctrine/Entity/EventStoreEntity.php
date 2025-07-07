<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'event_store')]
#[ORM\Index(columns: ['aggregate_id', 'version'], name: 'idx_aggregate_version')]
#[ORM\Index(columns: ['occurred_at'], name: 'idx_occurred_at')]
class EventStoreEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    public function __construct(
        #[ORM\Column(type: Types::STRING, length: 36)]
        private string $aggregateId,
        #[ORM\Column(type: Types::STRING, length: 255)]
        private string $eventType,
        #[ORM\Column(type: Types::JSON)]
        private string $eventData,
        #[ORM\Column(type: Types::INTEGER)]
        private int $version,
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
        private DateTimeImmutable $occurredAt
    )
    {
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

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
