<?php

declare(strict_types=1);

namespace App\Order\Application\Query;

use App\Order\Application\Dto\OrderDto;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use DomainException;

final readonly class GetOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {
    }

    public function __invoke(GetOrderQuery $query): OrderDto
    {
        $order = $this->orderRepository->load($query->orderId);
        
        if ($order === null) {
            throw new DomainException('Order not found');
        }

        return OrderDto::fromOrder($order);
    }
}