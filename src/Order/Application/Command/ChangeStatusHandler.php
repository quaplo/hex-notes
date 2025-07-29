<?php

declare(strict_types=1);

namespace App\Order\Application\Command;

use App\Shared\Domain\Model\AggregateRoot;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use DomainException;

final readonly class ChangeStatusHandler
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {
    }

    public function __invoke(ChangeStatusCommand $changeStatusCommand): void
    {
        $order = $this->orderRepository->load($changeStatusCommand->orderId);

        if (!$order instanceof AggregateRoot) {
            throw new DomainException('Order not found');
        }

        $order->changeStatus($changeStatusCommand->newStatus);

        $this->orderRepository->save($order);
    }
}
