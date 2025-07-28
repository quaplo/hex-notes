<?php

declare(strict_types=1);

namespace App\Order\Domain\ValueObject;

use App\Shared\ValueObject\Uuid;
use InvalidArgumentException;

final readonly class OrderItem
{
    public function __construct(
        private Uuid $orderItemId,
        private Uuid $productId,
        private string $productName,
        private int $quantity,
        private Money $unitPrice
    ) {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Order item quantity must be positive');
        }

        if (trim($productName) === '') {
            throw new InvalidArgumentException('Product name cannot be empty');
        }
    }

    public static function create(
        Uuid $productId,
        string $productName,
        int $quantity,
        Money $unitPrice
    ): self {
        return new self(
            Uuid::generate(),
            $productId,
            $productName,
            $quantity,
            $unitPrice
        );
    }

    public static function fromPrimitives(
        string $orderItemId,
        string $productId,
        string $productName,
        int $quantity,
        float $unitPrice,
        string $currency
    ): self {
        return new self(
            Uuid::create($orderItemId),
            Uuid::create($productId),
            $productName,
            $quantity,
            Money::fromAmountAndCurrency($unitPrice, $currency)
        );
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

    public function getTotalPrice(): Money
    {
        return $this->unitPrice->multiply($this->quantity);
    }

    public function changeQuantity(int $newQuantity): self
    {
        if ($newQuantity <= 0) {
            throw new InvalidArgumentException('Order item quantity must be positive');
        }

        return new self(
            $this->orderItemId,
            $this->productId,
            $this->productName,
            $newQuantity,
            $this->unitPrice
        );
    }

    public function equals(OrderItem $other): bool
    {
        return $this->orderItemId->equals($other->orderItemId);
    }

    public function isSameProduct(Uuid $productId): bool
    {
        return $this->productId->equals($productId);
    }
}