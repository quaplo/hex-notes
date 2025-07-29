<?php

declare(strict_types=1);

namespace App\Order\Domain\Event;

use App\Order\Domain\ValueObject\Currency;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final readonly class OrderCreatedEvent implements DomainEvent
{
    public function __construct(
        private Uuid $uuid,
        private Currency $currency,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {
    }

    public function getOrderId(): Uuid
    {
        return $this->uuid;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
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
        return 'order.created';
    }

    public function getEventData(): array
    {
        return [
            'orderId' => $this->uuid->toString(),
            'currency' => $this->currency->toString(),
            'occurredAt' => $this->occurredAt->format('Y-m-d H:i:s')
        ];
    }

    public static function fromEventData(array $eventData): self
    {
        return new self(
            Uuid::create($eventData['orderId']),
            Currency::fromString($eventData['currency']),
            new DateTimeImmutable($eventData['occurredAt'])
        );
    }
}
