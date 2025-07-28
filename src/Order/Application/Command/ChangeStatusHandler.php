<?php

declare(strict_types=1);

namespace App\Order\Application\Command;

use App\Order\Domain\Repository\OrderRepositoryInterface;
use DomainException;

final readonly class ChangeStatusHandler
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {
    }

    public function __invoke(ChangeStatusCommand $command): void
    {
        $order = $this->orderRepository->load($command->orderId);
        
        if ($order === null) {
            throw new DomainException('Order not found');
        }

        $order->changeStatus($command->newStatus);

        $this->orderRepository->save($order);
    }
}