<?php

declare(strict_types=1);

namespace App\Order\Application\Query;

use App\Shared\ValueObject\Uuid;

final readonly class GetOrderQuery
{
    public function __construct(
        public readonly Uuid $orderId
    ) {
    }

    public static function fromPrimitives(string $orderId): self
    {
        return new self(Uuid::create($orderId));
    }

    public function getOrderId(): Uuid
    {
        return $this->orderId;
    }
}