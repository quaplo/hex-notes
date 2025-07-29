<?php

declare(strict_types=1);

namespace App\Order\Domain\Model;

use DomainException;
use RuntimeException;
use App\Order\Domain\Event\ItemAddedEvent;
use App\Order\Domain\Event\ItemRemovedEvent;
use App\Order\Domain\Event\OrderCreatedEvent;
use App\Order\Domain\Event\OrderStatusChangedEvent;
use App\Order\Domain\ValueObject\Currency;
use App\Order\Domain\ValueObject\Money;
use App\Order\Domain\ValueObject\OrderItem;
use App\Order\Domain\ValueObject\OrderStatus;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final class Order extends AggregateRoot
{
    /**
     * @var OrderItem[]
     */
    private array $items = [];

    public function __construct(
        private Uuid $uuid,
        private DateTimeImmutable $createdAt,
        private Currency $currency,
        private OrderStatus $orderStatus
    ) {
    }

    public static function create(Currency $currency): self
    {
        $order = new self(
            Uuid::generate(),
            new DateTimeImmutable(),
            $currency,
            OrderStatus::CREATED
        );

        $order->apply(new OrderCreatedEvent($order->uuid, $currency));

        return $order;
    }

    /**
     * Creates an empty Order aggregate for Event Sourcing replay.
     * All properties will be set by replaying domain events.
     */
    public static function createEmpty(): self
    {
        return new self(
            Uuid::create('00000000-0000-0000-0000-000000000000'), // Null UUID
            new DateTimeImmutable('1970-01-01T00:00:00+00:00'), // Epoch time
            Currency::CZK, // Default currency
            OrderStatus::CREATED // Default status
        );
    }

    public function getId(): Uuid
    {
        return $this->uuid;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getStatus(): OrderStatus
    {
        return $this->orderStatus;
    }

    /**
     * @return OrderItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotalPrice(): Money
    {
        $total = Money::zero($this->currency);

        foreach ($this->items as $item) {
            $total = $total->add($item->getTotalPrice());
        }

        return $total;
    }

    public function addItem(Uuid $uuid, string $productName, int $quantity, Money $money): self
    {
        if (!$this->orderStatus->canBeModified()) {
            throw new DomainException('Cannot add items to order with status: ' . $this->orderStatus->toString());
        }

        if (!$money->getCurrency()->equals($this->currency)) {
            throw new DomainException('Item currency must match order currency');
        }

        // Check if product already exists in order
        foreach ($this->items as $item) {
            if ($item->isSameProduct($uuid)) {
                throw new DomainException('Product already exists in order. Use quantity change instead.');
            }
        }

        $orderItem = OrderItem::create($uuid, $productName, $quantity, $money);

        $this->apply(new ItemAddedEvent(
            $this->uuid,
            $orderItem->getOrderItemId(),
            $uuid,
            $productName,
            $quantity,
            $money
        ));

        return $this;
    }

    public function removeItem(Uuid $uuid): self
    {
        if (!$this->orderStatus->canBeModified()) {
            throw new DomainException('Cannot remove items from order with status: ' . $this->orderStatus->toString());
        }

        $found = false;
        foreach ($this->items as $item) {
            if ($item->getOrderItemId()->equals($uuid)) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new DomainException('Order item not found');
        }

        $this->apply(new ItemRemovedEvent($this->uuid, $uuid));

        return $this;
    }

    public function changeStatus(OrderStatus $orderStatus): self
    {
        if ($this->orderStatus === $orderStatus) {
            return $this; // No change needed
        }

        // Business rules for status transitions
        $this->validateStatusTransition($orderStatus);

        $oldStatus = $this->orderStatus;
        $this->apply(new OrderStatusChangedEvent($this->uuid, $oldStatus, $orderStatus));

        return $this;
    }

    public function cancel(): self
    {
        if (!$this->orderStatus->canBeCancelled()) {
            throw new DomainException('Order cannot be cancelled in current status: ' . $this->orderStatus->toString());
        }

        return $this->changeStatus(OrderStatus::CANCELLED);
    }

    /**
     * Add item directly without domain events (for event replay)
     */
    public function restoreItem(OrderItem $orderItem): void
    {
        $this->items[] = $orderItem;
    }

    /**
     * Implementation of abstract handleEvent method from AggregateRoot
     */
    protected function handleEvent(DomainEvent $domainEvent): void
    {
        match ($domainEvent::class) {
            OrderCreatedEvent::class => $this->handleOrderCreated($domainEvent),
            ItemAddedEvent::class => $this->handleItemAdded($domainEvent),
            ItemRemovedEvent::class => $this->handleItemRemoved($domainEvent),
            OrderStatusChangedEvent::class => $this->handleOrderStatusChanged($domainEvent),
            default => throw new RuntimeException('Unknown event type: ' . $domainEvent::class)
        };
    }

    private function handleOrderCreated(OrderCreatedEvent $orderCreatedEvent): void
    {
        $this->uuid = $orderCreatedEvent->getOrderId();
        $this->currency = $orderCreatedEvent->getCurrency();
        $this->orderStatus = OrderStatus::CREATED;
        $this->createdAt = $orderCreatedEvent->getOccurredAt();
    }

    private function handleItemAdded(ItemAddedEvent $itemAddedEvent): void
    {
        $orderItem = OrderItem::fromPrimitives(
            $itemAddedEvent->getOrderItemId()->toString(),
            $itemAddedEvent->getProductId()->toString(),
            $itemAddedEvent->getProductName(),
            $itemAddedEvent->getQuantity(),
            $itemAddedEvent->getUnitPrice()->getAmount(),
            $itemAddedEvent->getUnitPrice()->getCurrency()->toString()
        );

        $this->items[] = $orderItem;
    }

    private function handleItemRemoved(ItemRemovedEvent $itemRemovedEvent): void
    {
        $this->items = array_filter(
            $this->items,
            fn(OrderItem $orderItem): bool => !$orderItem->getOrderItemId()->equals($itemRemovedEvent->getOrderItemId())
        );

        // Re-index array
        $this->items = array_values($this->items);
    }

    private function handleOrderStatusChanged(OrderStatusChangedEvent $orderStatusChangedEvent): void
    {
        $this->orderStatus = $orderStatusChangedEvent->getNewStatus();
    }

    private function validateStatusTransition(OrderStatus $orderStatus): void
    {
        // Business rules for valid status transitions
        $validTransitions = match ($this->orderStatus) {
            OrderStatus::CREATED => [OrderStatus::CONFIRMED, OrderStatus::CANCELLED],
            OrderStatus::CONFIRMED => [OrderStatus::PAID, OrderStatus::CANCELLED],
            OrderStatus::PAID => [OrderStatus::SHIPPED, OrderStatus::REFUNDED],
            OrderStatus::SHIPPED => [OrderStatus::DELIVERED],
            OrderStatus::DELIVERED => [OrderStatus::REFUNDED],
            OrderStatus::CANCELLED => [], // Terminal state
            OrderStatus::REFUNDED => [], // Terminal state
        };

        if (!in_array($orderStatus, $validTransitions, true)) {
            throw new DomainException(
                sprintf(
                    'Invalid status transition from %s to %s',
                    $this->orderStatus->toString(),
                    $orderStatus->toString()
                )
            );
        }
    }
}
