# Domain: Order (Event-Sourced, CQRS)

## ⚖️ Purpose

This document defines the structure, behavior, and expectations for implementing the **Order domain** in a CQRS + Event Sourcing architecture. It serves as an agreement for AI agents generating code and logic related to this domain in the context of an existing DDD-based system.

---

## 🌐 Context

The Order domain is responsible for modeling customer orders. Each order is built over time through a series of events (user actions) and reflects a snapshot of products the customer intends to purchase.

---

## 🔄 Architectural Requirements

* Implemented using **Event Sourcing** (no mutable state; all state derived from events)
* Uses **CQRS** pattern
* Fully consistent in its aggregate boundary
* Event replay defines the current state

---

## 🔍 Aggregate: `Order`

### Attributes:

* `OrderId $orderId`
* `\DateTimeImmutable $createdAt`
* `Currency $currency` (Value Object)
* `Status $status` (Enum: Draft, Submitted, Cancelled, etc.)
* `OrderItem[] $items` (Map of OrderItemId -> OrderItem)

### Behavior:

* `addItem(...)`
* `removeItem(...)`
* `submit()`
* `cancel()`

### Invariants:

* Quantity must not go below 0
* Prices must not change for existing `OrderItem`
* Cannot modify submitted/cancelled orders

---

## 📅 Entity: `OrderItem`

### Identity:

* Identified by `OrderItemId` (UUID)

### Attributes:

* `OrderItemId $id`
* `ProductId $productId`
* `string $productName`
* `Money $unitPrice`
* `int $quantity`

### Notes:

* `productId` is a reference, not identity
* Each addition with a different unit price results in a new OrderItem
* Immutable in value, mutable in quantity

---

## 📆 Events

### `OrderCreated`

```php
OrderCreated {
    OrderId $orderId,
    \DateTimeImmutable $createdAt,
    Currency $currency
}
```

### `OrderItemAdded`

```php
OrderItemAdded {
    OrderId $orderId,
    OrderItemId $orderItemId,
    ProductId $productId,
    string $productName,
    Money $unitPrice,
    int $quantity
}
```

### `OrderItemRemoved`

```php
OrderItemRemoved {
    OrderId $orderId,
    OrderItemId $orderItemId,
    int $quantity
}
```

### `OrderSubmitted`

```php
OrderSubmitted {
    OrderId $orderId,
    \DateTimeImmutable $submittedAt
}
```

### `OrderCancelled`

```php
OrderCancelled {
    OrderId $orderId,
    \DateTimeImmutable $cancelledAt,
    string $reason
}
```

---

## 📢 Commands

### `CreateOrder`

### `AddOrderItem`

### `RemoveOrderItem`

### `SubmitOrder`

### `CancelOrder`

Each command is processed via an application service / command handler that emits corresponding domain events.

---

## 🔄 Replay Mechanism

AI agent must implement `Order::rehydrate(EventStream)` to replay and reconstruct aggregate state.

---

## 🕊️ Read Model Projection

AI agent should build a projection:

```json
{
  "orderId": "UUID",
  "createdAt": "datetime",
  "currency": "CZK",
  "status": "Submitted",
  "price": 1234.00,
  "items": [
    {
      "orderItemId": "UUID",
      "productId": "UUID",
      "productName": "Some Product",
      "unitPrice": 456.00,
      "quantity": 2
    }
  ]
}
```

---

## ⚡ Summary

This domain captures the lifecycle of a customer order through event sourcing. `OrderItem` is treated as an entity because of its mutable state (`quantity`) and pricing history. All state transitions are event-driven. AI agents working within this domain must adhere strictly to these structures and rules.
