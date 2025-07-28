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

    public function __invoke(CreateOrderCommand $command): Uuid
    {
        $order = Order::create($command->currency);
        
        $this->orderRepository->save($order);
        
        return $order->getId();
    }
}