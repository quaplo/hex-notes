<?php

declare(strict_types=1);

namespace App\Order\Application\Command;

use App\Shared\Domain\Model\AggregateRoot;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use DomainException;

final readonly class RemoveItemHandler
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {
    }

    public function __invoke(RemoveItemCommand $removeItemCommand): void
    {
        $order = $this->orderRepository->load($removeItemCommand->orderId);

        if (!$order instanceof AggregateRoot) {
            throw new DomainException('Order not found');
        }

        $order->removeItem($removeItemCommand->orderItemId);

        $this->orderRepository->save($order);
    }
}
