<?php

declare(strict_types=1);

namespace App\Order\Application\Command;

use App\Shared\Domain\Model\AggregateRoot;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use DomainException;

final readonly class AddItemHandler
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {
    }

    public function __invoke(AddItemCommand $addItemCommand): void
    {
        $order = $this->orderRepository->load($addItemCommand->orderId);

        if (!$order instanceof AggregateRoot) {
            throw new DomainException('Order not found');
        }

        $order->addItem(
            $addItemCommand->productId,
            $addItemCommand->productName,
            $addItemCommand->quantity,
            $addItemCommand->unitPrice
        );

        $this->orderRepository->save($order);
    }
}
