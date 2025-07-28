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
        private Uuid $orderId,
        private DateTimeImmutable $createdAt,
        private Currency $currency,
        private OrderStatus $status
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
        
        $order->apply(new OrderCreatedEvent($order->orderId, $currency));
        
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
        return $this->orderId;
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
        return $this->status;
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

    public function addItem(Uuid $productId, string $productName, int $quantity, Money $unitPrice): self
    {
        if (!$this->status->canBeModified()) {
            throw new DomainException('Cannot add items to order with status: ' . $this->status->toString());
        }

        if (!$unitPrice->getCurrency()->equals($this->currency)) {
            throw new DomainException('Item currency must match order currency');
        }

        // Check if product already exists in order
        foreach ($this->items as $existingItem) {
            if ($existingItem->isSameProduct($productId)) {
                throw new DomainException('Product already exists in order. Use quantity change instead.');
            }
        }

        $orderItem = OrderItem::create($productId, $productName, $quantity, $unitPrice);

        $this->apply(new ItemAddedEvent(
            $this->orderId,
            $orderItem->getOrderItemId(),
            $productId,
            $productName,
            $quantity,
            $unitPrice
        ));

        return $this;
    }

    public function removeItem(Uuid $orderItemId): self
    {
        if (!$this->status->canBeModified()) {
            throw new DomainException('Cannot remove items from order with status: ' . $this->status->toString());
        }

        $found = false;
        foreach ($this->items as $item) {
            if ($item->getOrderItemId()->equals($orderItemId)) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new DomainException('Order item not found');
        }

        $this->apply(new ItemRemovedEvent($this->orderId, $orderItemId));

        return $this;
    }

    public function changeStatus(OrderStatus $newStatus): self
    {
        if ($this->status === $newStatus) {
            return $this; // No change needed
        }

        // Business rules for status transitions
        $this->validateStatusTransition($newStatus);

        $oldStatus = $this->status;
        $this->apply(new OrderStatusChangedEvent($this->orderId, $oldStatus, $newStatus));

        return $this;
    }

    public function cancel(): self
    {
        if (!$this->status->canBeCancelled()) {
            throw new DomainException('Order cannot be cancelled in current status: ' . $this->status->toString());
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

    private function handleOrderCreated(OrderCreatedEvent $event): void
    {
        $this->orderId = $event->getOrderId();
        $this->currency = $event->getCurrency();
        $this->status = OrderStatus::CREATED;
        $this->createdAt = $event->getOccurredAt();
    }

    private function handleItemAdded(ItemAddedEvent $event): void
    {
        $orderItem = OrderItem::fromPrimitives(
            $event->getOrderItemId()->toString(),
            $event->getProductId()->toString(),
            $event->getProductName(),
            $event->getQuantity(),
            $event->getUnitPrice()->getAmount(),
            $event->getUnitPrice()->getCurrency()->toString()
        );

        $this->items[] = $orderItem;
    }

    private function handleItemRemoved(ItemRemovedEvent $event): void
    {
        $this->items = array_filter(
            $this->items,
            fn(OrderItem $item): bool => !$item->getOrderItemId()->equals($event->getOrderItemId())
        );
        
        // Re-index array
        $this->items = array_values($this->items);
    }

    private function handleOrderStatusChanged(OrderStatusChangedEvent $event): void
    {
        $this->status = $event->getNewStatus();
    }

    private function validateStatusTransition(OrderStatus $newStatus): void
    {
        // Business rules for valid status transitions
        $validTransitions = match ($this->status) {
            OrderStatus::CREATED => [OrderStatus::CONFIRMED, OrderStatus::CANCELLED],
            OrderStatus::CONFIRMED => [OrderStatus::PAID, OrderStatus::CANCELLED],
            OrderStatus::PAID => [OrderStatus::SHIPPED, OrderStatus::REFUNDED],
            OrderStatus::SHIPPED => [OrderStatus::DELIVERED],
            OrderStatus::DELIVERED => [OrderStatus::REFUNDED],
            OrderStatus::CANCELLED => [], // Terminal state
            OrderStatus::REFUNDED => [], // Terminal state
        };

        if (!in_array($newStatus, $validTransitions, true)) {
            throw new DomainException(
                sprintf(
                    'Invalid status transition from %s to %s',
                    $this->status->toString(),
                    $newStatus->toString()
                )
            );
        }
    }
}