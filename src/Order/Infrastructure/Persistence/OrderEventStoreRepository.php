<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence;

use App\Infrastructure\Persistence\EventStore\AbstractEventStoreRepository;
use App\Order\Domain\Model\Order;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use App\Shared\Domain\Model\AggregateRoot;

final class OrderEventStoreRepository extends AbstractEventStoreRepository implements OrderRepositoryInterface
{
    protected function createAggregate(): AggregateRoot
    {
        return Order::createEmpty();
    }
}