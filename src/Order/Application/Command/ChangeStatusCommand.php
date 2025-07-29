<?php

declare(strict_types=1);

namespace App\Order\Application\Command;

use App\Order\Domain\ValueObject\OrderStatus;
use App\Shared\ValueObject\Uuid;

final readonly class ChangeStatusCommand
{
    private function __construct(
        public Uuid $orderId,
        public OrderStatus $newStatus
    ) {
    }

    public static function fromPrimitives(string $orderId, string $newStatus): self
    {
        return new self(
            Uuid::create($orderId),
            OrderStatus::fromString($newStatus)
        );
    }

    public static function create(Uuid $uuid, OrderStatus $orderStatus): self
    {
        return new self($uuid, $orderStatus);
    }
}
