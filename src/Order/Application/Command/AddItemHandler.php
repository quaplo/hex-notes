<?php

declare(strict_types=1);

namespace App\Order\Application\Command;

use App\Order\Domain\Repository\OrderRepositoryInterface;
use DomainException;

final readonly class AddItemHandler
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {
    }

    public function __invoke(AddItemCommand $command): void
    {
        $order = $this->orderRepository->load($command->orderId);
        
        if ($order === null) {
            throw new DomainException('Order not found');
        }

        $order->addItem(
            $command->productId,
            $command->productName,
            $command->quantity,
            $command->unitPrice
        );

        $this->orderRepository->save($order);
    }
}