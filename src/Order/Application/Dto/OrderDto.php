<?php

declare(strict_types=1);

namespace App\Order\Application\Dto;

use App\Order\Domain\Model\Order;
use DateTimeImmutable;

final readonly class OrderDto
{
    /**
     * @param OrderItemDto[] $items
     */
    public function __construct(
        public string $orderId,
        public DateTimeImmutable $createdAt,
        public float $totalPrice,
        public string $currency,
        public string $status,
        public array $items
    ) {
    }

    public static function fromOrder(Order $order): self
    {
        $items = array_map(
            fn($item) => OrderItemDto::fromOrderItem($item),
            $order->getItems()
        );

        return new self(
            $order->getId()->toString(),
            $order->getCreatedAt(),
            $order->getTotalPrice()->getAmount(),
            $order->getCurrency()->toString(),
            $order->getStatus()->toString(),
            $items
        );
    }
}