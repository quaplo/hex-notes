<?php

declare(strict_types=1);

namespace App\Order\Application\Command;

use App\Order\Domain\ValueObject\Money;
use App\Shared\ValueObject\Uuid;

final readonly class AddItemCommand
{
    private function __construct(
        public Uuid $orderId,
        public Uuid $productId,
        public string $productName,
        public int $quantity,
        public Money $unitPrice
    ) {
    }

    public static function fromPrimitives(
        string $orderId,
        string $productId,
        string $productName,
        int $quantity,
        float $unitPrice,
        string $currency
    ): self {
        return new self(
            Uuid::create($orderId),
            Uuid::create($productId),
            $productName,
            $quantity,
            Money::fromAmountAndCurrency($unitPrice, $currency)
        );
    }

    public static function create(
        Uuid $orderId,
        Uuid $productId,
        string $productName,
        int $quantity,
        Money $unitPrice
    ): self {
        return new self($orderId, $productId, $productName, $quantity, $unitPrice);
    }
}