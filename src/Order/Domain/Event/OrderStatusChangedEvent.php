<?php

declare(strict_types=1);

namespace App\Order\Domain\Event;

use App\Order\Domain\ValueObject\OrderStatus;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final readonly class OrderStatusChangedEvent implements DomainEvent
{
    public function __construct(
        private Uuid $uuid,
        private OrderStatus $oldStatus,
        private OrderStatus $newStatus,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {
    }

    public function getOrderId(): Uuid
    {
        return $this->uuid;
    }

    public function getOldStatus(): OrderStatus
    {
        return $this->oldStatus;
    }

    public function getNewStatus(): OrderStatus
    {
        return $this->newStatus;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getAggregateId(): string
    {
        return $this->uuid->toString();
    }

    public function getEventName(): string
    {
        return 'order.status.changed';
    }

    public function getEventData(): array
    {
        return [
            'orderId' => $this->uuid->toString(),
            'oldStatus' => $this->oldStatus->toString(),
            'newStatus' => $this->newStatus->toString(),
            'occurredAt' => $this->occurredAt->format('Y-m-d H:i:s')
        ];
    }

    public static function fromEventData(array $eventData): self
    {
        return new self(
            Uuid::create($eventData['orderId']),
            OrderStatus::fromString($eventData['oldStatus']),
            OrderStatus::fromString($eventData['newStatus']),
            new DateTimeImmutable($eventData['occurredAt'])
        );
    }
}
