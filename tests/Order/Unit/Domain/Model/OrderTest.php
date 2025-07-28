<?php

declare(strict_types=1);

use App\Order\Domain\Model\Order;
use App\Order\Domain\ValueObject\Currency;
use App\Order\Domain\ValueObject\Money;
use App\Order\Domain\ValueObject\OrderStatus;
use App\Shared\ValueObject\Uuid;

describe('Order Domain Model', function (): void {

    test('order can be created with valid currency', function (): void {
        $currency = Currency::CZK;
        $order = Order::create($currency);

        expect($order->getId())->toBeInstanceOf(Uuid::class);
        expect($order->getCurrency())->toBe($currency);
        expect($order->getStatus())->toBe(OrderStatus::CREATED);
        expect($order->getCreatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
        expect($order->getItems())->toBeEmpty();
        expect($order->getTotalPrice()->isZero())->toBeTrue();
        expect($order->getDomainEvents())->toHaveCount(1);
    });

    test('order creation records OrderCreatedEvent', function (): void {
        $currency = Currency::EUR;
        $order = Order::create($currency);
        $events = $order->getDomainEvents();

        expect($events)->toHaveCount(1);
        expect($events[0])->toBeInstanceOf(\App\Order\Domain\Event\OrderCreatedEvent::class);
        expect($events[0]->getOrderId()->equals($order->getId()))->toBeTrue();
        expect($events[0]->getCurrency())->toBe($currency);
    });

    test('item can be added to order', function (): void {
        $order = Order::create(Currency::CZK);
        $productId = Uuid::generate();
        $unitPrice = Money::fromAmountAndCurrency(100.0, 'CZK');

        $order->addItem($productId, 'Test Product', 2, $unitPrice);

        expect($order->getItems())->toHaveCount(1);
        expect($order->getItems()[0]->getProductId()->equals($productId))->toBeTrue();
        expect($order->getItems()[0]->getProductName())->toBe('Test Product');
        expect($order->getItems()[0]->getQuantity())->toBe(2);
        expect($order->getTotalPrice()->getAmount())->toBe(200.0);
        expect($order->getDomainEvents())->toHaveCount(2); // OrderCreated + ItemAdded
    });

    test('adding item records ItemAddedEvent', function (): void {
        $order = Order::create(Currency::USD);
        $productId = Uuid::generate();
        $unitPrice = Money::fromAmountAndCurrency(50.0, 'USD');

        $order->addItem($productId, 'Another Product', 1, $unitPrice);
        $events = $order->getDomainEvents();

        expect($events)->toHaveCount(2);
        expect($events[1])->toBeInstanceOf(\App\Order\Domain\Event\ItemAddedEvent::class);
        expect($events[1]->getProductId()->equals($productId))->toBeTrue();
        expect($events[1]->getProductName())->toBe('Another Product');
        expect($events[1]->getQuantity())->toBe(1);
    });

    test('cannot add item with different currency', function (): void {
        $order = Order::create(Currency::CZK);
        $productId = Uuid::generate();
        $unitPrice = Money::fromAmountAndCurrency(100.0, 'EUR');

        expect(fn() => $order->addItem($productId, 'Test Product', 1, $unitPrice))
            ->toThrow(\DomainException::class, 'Item currency must match order currency');
    });

    test('cannot add duplicate product', function (): void {
        $order = Order::create(Currency::CZK);
        $productId = Uuid::generate();
        $unitPrice = Money::fromAmountAndCurrency(100.0, 'CZK');

        $order->addItem($productId, 'Test Product', 1, $unitPrice);

        expect(fn() => $order->addItem($productId, 'Test Product Again', 2, $unitPrice))
            ->toThrow(\DomainException::class, 'Product already exists in order');
    });

    test('item can be removed from order', function (): void {
        $order = Order::create(Currency::CZK);
        $productId = Uuid::generate();
        $unitPrice = Money::fromAmountAndCurrency(100.0, 'CZK');

        $order->addItem($productId, 'Test Product', 1, $unitPrice);
        $orderItemId = $order->getItems()[0]->getOrderItemId();

        $order->removeItem($orderItemId);

        expect($order->getItems())->toBeEmpty();
        expect($order->getTotalPrice()->isZero())->toBeTrue();
        expect($order->getDomainEvents())->toHaveCount(3); // OrderCreated + ItemAdded + ItemRemoved
    });

    test('removing item records ItemRemovedEvent', function (): void {
        $order = Order::create(Currency::CZK);
        $productId = Uuid::generate();
        $unitPrice = Money::fromAmountAndCurrency(100.0, 'CZK');

        $order->addItem($productId, 'Test Product', 1, $unitPrice);
        $orderItemId = $order->getItems()[0]->getOrderItemId();
        $order->removeItem($orderItemId);

        $events = $order->getDomainEvents();
        expect($events)->toHaveCount(3);
        expect($events[2])->toBeInstanceOf(\App\Order\Domain\Event\ItemRemovedEvent::class);
        expect($events[2]->getOrderItemId()->equals($orderItemId))->toBeTrue();
    });

    test('cannot remove non-existent item', function (): void {
        $order = Order::create(Currency::CZK);
        $nonExistentItemId = Uuid::generate();

        expect(fn() => $order->removeItem($nonExistentItemId))
            ->toThrow(\DomainException::class, 'Order item not found');
    });

    test('order status can be changed', function (): void {
        $order = Order::create(Currency::CZK);

        $order->changeStatus(OrderStatus::CONFIRMED);

        expect($order->getStatus())->toBe(OrderStatus::CONFIRMED);
        expect($order->getDomainEvents())->toHaveCount(2); // OrderCreated + StatusChanged
    });

    test('changing status records OrderStatusChangedEvent', function (): void {
        $order = Order::create(Currency::CZK);

        $order->changeStatus(OrderStatus::PAID);
        $events = $order->getDomainEvents();

        expect($events)->toHaveCount(2);
        expect($events[1])->toBeInstanceOf(\App\Order\Domain\Event\OrderStatusChangedEvent::class);
        expect($events[1]->getOldStatus())->toBe(OrderStatus::CREATED);
        expect($events[1]->getNewStatus())->toBe(OrderStatus::PAID);
    });

    test('invalid status transition throws exception', function (): void {
        $order = Order::create(Currency::CZK);

        expect(fn() => $order->changeStatus(OrderStatus::DELIVERED))
            ->toThrow(\DomainException::class, 'Invalid status transition');
    });

    test('order can be cancelled', function (): void {
        $order = Order::create(Currency::CZK);

        $order->cancel();

        expect($order->getStatus())->toBe(OrderStatus::CANCELLED);
    });

    test('cannot cancel delivered order', function (): void {
        $order = Order::create(Currency::CZK);
        $order->changeStatus(OrderStatus::CONFIRMED);
        $order->changeStatus(OrderStatus::PAID);
        $order->changeStatus(OrderStatus::SHIPPED);
        $order->changeStatus(OrderStatus::DELIVERED);

        expect(fn() => $order->cancel())
            ->toThrow(\DomainException::class, 'Order cannot be cancelled in current status');
    });

    test('cannot add items to non-modifiable order', function (): void {
        $order = Order::create(Currency::CZK);
        $order->changeStatus(OrderStatus::CONFIRMED);
        $order->changeStatus(OrderStatus::PAID);

        $productId = Uuid::generate();
        $unitPrice = Money::fromAmountAndCurrency(100.0, 'CZK');

        expect(fn() => $order->addItem($productId, 'Test Product', 1, $unitPrice))
            ->toThrow(\DomainException::class, 'Cannot add items to order with status');
    });

    test('cannot remove items from non-modifiable order', function (): void {
        $order = Order::create(Currency::CZK);
        $productId = Uuid::generate();
        $unitPrice = Money::fromAmountAndCurrency(100.0, 'CZK');
        $order->addItem($productId, 'Test Product', 1, $unitPrice);
        $orderItemId = $order->getItems()[0]->getOrderItemId();

        $order->changeStatus(OrderStatus::CONFIRMED);
        $order->changeStatus(OrderStatus::PAID);

        expect(fn() => $order->removeItem($orderItemId))
            ->toThrow(\DomainException::class, 'Cannot remove items from order with status');
    });
});