<?php

declare(strict_types=1);

namespace App\Order\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final readonly class ItemRemovedEvent implements DomainEvent
{
    public function __construct(
        private Uuid $orderId,
        private Uuid $orderItemId,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {
    }

    public function getOrderId(): Uuid
    {
        return $this->orderId;
    }

    public function getOrderItemId(): Uuid
    {
        return $this->orderItemId;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getAggregateId(): string
    {
        return $this->orderId->toString();
    }

    public function getEventName(): string
    {
        return 'order.item.removed';
    }

    public function getEventData(): array
    {
        return [
            'orderId' => $this->orderId->toString(),
            'orderItemId' => $this->orderItemId->toString(),
            'occurredAt' => $this->occurredAt->format('Y-m-d H:i:s')
        ];
    }

    public static function fromEventData(array $eventData): self
    {
        return new self(
            Uuid::create($eventData['orderId']),
            Uuid::create($eventData['orderItemId']),
            new DateTimeImmutable($eventData['occurredAt'])
        );
    }
}