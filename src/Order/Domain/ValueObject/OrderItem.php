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
        private Money $money
    ) {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Order item quantity must be positive');
        }

        if (trim($productName) === '') {
            throw new InvalidArgumentException('Product name cannot be empty');
        }
    }

    public static function create(
        Uuid $uuid,
        string $productName,
        int $quantity,
        Money $money
    ): self {
        return new self(
            Uuid::generate(),
            $uuid,
            $productName,
            $quantity,
            $money
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
        return $this->money;
    }

    public function getTotalPrice(): Money
    {
        return $this->money->multiply($this->quantity);
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
            $this->money
        );
    }

    public function equals(OrderItem $orderItem): bool
    {
        return $this->orderItemId->equals($orderItem->orderItemId);
    }

    public function isSameProduct(Uuid $uuid): bool
    {
        return $this->productId->equals($uuid);
    }
}
