<?php

declare(strict_types=1);

namespace App\Order\Application\Command;

use App\Shared\ValueObject\Uuid;

final readonly class RemoveItemCommand
{
    private function __construct(
        public Uuid $orderId,
        public Uuid $orderItemId
    ) {
    }

    public static function fromPrimitives(string $orderId, string $orderItemId): self
    {
        return new self(
            Uuid::create($orderId),
            Uuid::create($orderItemId)
        );
    }

    public static function create(Uuid $orderId, Uuid $orderItemId): self
    {
        return new self($orderId, $orderItemId);
    }
}