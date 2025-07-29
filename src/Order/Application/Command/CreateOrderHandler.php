<?php

declare(strict_types=1);

namespace App\Order\Application\Command;

use App\Order\Domain\Model\Order;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use App\Shared\ValueObject\Uuid;

final readonly class CreateOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {
    }

    public function __invoke(CreateOrderCommand $createOrderCommand): Uuid
    {
        $order = Order::create($createOrderCommand->currency);

        $this->orderRepository->save($order);

        return $order->getId();
    }
}
