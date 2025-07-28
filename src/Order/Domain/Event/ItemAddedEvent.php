<?php

declare(strict_types=1);

namespace App\Order\Domain\Event;

use App\Order\Domain\ValueObject\Money;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final readonly class ItemAddedEvent implements DomainEvent
{
    public function __construct(
        private Uuid $orderId,
        private Uuid $orderItemId,
        private Uuid $productId,
        private string $productName,
        private int $quantity,
        private Money $unitPrice,
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

    public function getProductId(): Uuid
    {
        return $this->productId;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getUnitPrice(): Money
    {
        return $this->unitPrice;
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
        return 'order.item.added';
    }

    public function getEventData(): array
    {
        return [
            'orderId' => $this->orderId->toString(),
            'orderItemId' => $this->orderItemId->toString(),
            'productId' => $this->productId->toString(),
            'productName' => $this->productName,
            'quantity' => $this->quantity,
            'unitPrice' => $this->unitPrice->getAmount(),
            'currency' => $this->unitPrice->getCurrency()->toString(),
            'occurredAt' => $this->occurredAt->format('Y-m-d H:i:s')
        ];
    }

    public static function fromEventData(array $eventData): self
    {
        return new self(
            Uuid::create($eventData['orderId']),
            Uuid::create($eventData['orderItemId']),
            Uuid::create($eventData['productId']),
            $eventData['productName'],
            $eventData['quantity'],
            Money::fromAmountAndCurrency($eventData['unitPrice'], $eventData['currency']),
            new DateTimeImmutable($eventData['occurredAt'])
        );
    }
}