<?php

declare(strict_types=1);

namespace App\Order\Application\Dto;

use App\Order\Domain\ValueObject\OrderItem;

final readonly class OrderItemDto
{
    public function __construct(
        public string $orderItemId,
        public string $productId,
        public string $productName,
        public int $quantity,
        public float $unitPrice,
        public float $totalPrice,
        public string $currency
    ) {
    }

    public static function fromOrderItem(OrderItem $orderItem): self
    {
        return new self(
            $orderItem->getOrderItemId()->toString(),
            $orderItem->getProductId()->toString(),
            $orderItem->getProductName(),
            $orderItem->getQuantity(),
            $orderItem->getUnitPrice()->getAmount(),
            $orderItem->getTotalPrice()->getAmount(),
            $orderItem->getUnitPrice()->getCurrency()->toString()
        );
    }
}