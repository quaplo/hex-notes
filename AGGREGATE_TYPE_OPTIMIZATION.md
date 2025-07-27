# Aggregate Type Optimization for Event Store

## Prehľad

Táto dokumentácia popisuje optimalizáciu `event_store` tabuľky pridaním `aggregate_type` stĺpca pre lepšiu separáciu domén a výkonnosť queries pre Event Sourcing.

## Implementované zmeny

### 1. Databázová schéma (Migration: Version20250727114900)

**Pridané:**
- `aggregate_type VARCHAR(255) NOT NULL` stĺpec
- Nové indexy optimalizované pre domain queries:
  - `idx_aggregate_type_id_version` (aggregate_type, aggregate_id, version)
  - `idx_aggregate_type_occurred_at` (aggregate_type, occurred_at)
  - Zachovaný `idx_occurred_at` pre cross-domain queries

**Namespace mapping:**
- `App\Project\Domain\Event\*` → `"App\\Project"`
- `App\User\Domain\Event\*` → `"App\\User"`
- `App\Shared\Domain\Event\*` → `"App\\Shared"`

### 2. **Primary Key Optimization (Migration: Version20250727135000)**

**Replaced:**
- Artificial `INT AUTO_INCREMENT id` primary key
- Business-irrelevant technical identifier

**With:**
- Compound primary key: `(aggregate_id, version)`
- Natural Event Sourcing identifier
- Optimized indexes: `idx_aggregate_type`, `idx_aggregate_type_occurred_at`, `idx_occurred_at`

### 3. Kód zmeny

#### EventStoreEntity
- Pridaný `aggregateType` property s getter metódou
- Updatované Doctrine anotácie pre nové indexy

#### AggregateTypeResolver
- Automatická derivácia aggregate_type z event class namespace
- Podporuje existujúce aj budúce domény
- Validácia namespace štruktúry

#### DoctrineEventStore
- Automatické nastavenie aggregate_type pri insert operáciách
- Optimalizované query metódy využívajúce aggregate_type index
- Backward compatibility pre existujúce metódy

#### EventStore Interface
Nové optimalizované metódy:
- `getEventsByAggregateType(string $aggregateType, ?DateTimeImmutable $from, ?DateTimeImmutable $to)`
- `getEventsByAggregateTypeAndId(string $aggregateType, Uuid $aggregateId)`
- `getAggregateIdsByType(string $aggregateType)`

## Compound Primary Key prínosy

### **Event Sourcing Best Practice**
```php
// Prirodzený identifikátor pre ES events
PRIMARY KEY (aggregate_id, version)

// Namiesto umelého
id INT AUTO_INCREMENT PRIMARY KEY  // ❌ Nie je business relevant
```

### **Performance zlepšenie**
```sql
-- Optimalizované queries využívajú prirodzený PK
SELECT * FROM event_store
WHERE aggregate_id = ? AND version >= ?
-- Direct index hit na compound PK!

-- Vs pôvodný prístup s dodatočným where
SELECT * FROM event_store
WHERE id IN (...) AND aggregate_id = ? AND version >= ?
```

### **Storage úspora**
- Bez redundantného INT id stĺpca
- Jeden index namiesto dvoch (PK + aggregate_version)
- Menšie index size = rýchlejšie queries

## Prínosy pre Order doménu

### 1. Performance
```php
// Optimalizované query len pre Order eventy
$orderEvents = $eventStore->getEventsByAggregateType('App\\Order');

// Namiesto skenovánia celej event_store tabuľky
$allEvents = $eventStore->getEvents($uuid); // skenuje všetky domény
```

### 2. Domain Isolation
```php
// Izolované Order aggregate IDs
$orderAggregates = $eventStore->getAggregateIdsByType('App\\Order');

// Time-range queries pre Order domain
$recentOrderEvents = $eventStore->getEventsByAggregateType(
    'App\\Order', 
    new DateTimeImmutable('-1 month')
);
```

### 3. Automatická konfigurácia
```php
// Order events budú automaticky tagged s "App\\Order"
class OrderCreatedEvent implements DomainEvent
{
    // AggregateTypeResolver automaticky detekuje namespace
    // a nastaví aggregate_type = "App\\Order"
}
```

## Migrácia existujúcich dát

Migrácia automaticky:
1. Pridá `aggregate_type` stĺpec ako nullable
2. Populuje existujúce záznamy na základe `event_type` namespace
3. Nastaví NOT NULL constraint
4. Vytvorí optimalizované indexy

## Testovanie

Vytvorený unit test `AggregateTypeResolverTest` overuje:
- ✅ Správnu deriváciu pre Project events → `"App\\Project"`
- ✅ Správnu deriváciu pre User events → `"App\\User"`  
- ✅ Správnu deriváciu pre Shared events → `"App\\Shared"`
- ✅ Simuláciu budúcej Order domény → `"App\\Order"`
- ✅ Validáciu neplatných namespace štruktúr

## Príklad použitia pre Order doménu

### 1. Event definícia
```php
namespace App\Order\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;

final readonly class OrderCreatedEvent implements DomainEvent
{
    // Automaticky bude tagged s aggregate_type = "App\\Order"
}
```

### 2. Query optimalizácia
```php
// Optimalizované pre Order domain - využíva index
$orderEvents = $eventStore->getEventsByAggregateType('App\\Order');

// Špecifický Order aggregate
$singleOrderEvents = $eventStore->getEventsByAggregateTypeAndId(
    'App\\Order', 
    $orderId
);
```

### 3. Cross-domain analytics
```php
// Rýchle získanie všetkých Order aggregates
$orderIds = $eventStore->getAggregateIdsByType('App\\Order');

// Time-based analytics pre Orders
$monthlyOrders = $eventStore->getEventsByAggregateType(
    'App\\Order',
    new DateTimeImmutable('first day of this month'),
    new DateTimeImmutable('last day of this month')
);
```

## Backward Compatibility

- ✅ Všetky existujúce metódy fungujú bez zmien
- ✅ Existujúce queries zostávajú funkčné
- ✅ Postupné migrácia bez downtime
- ✅ Nové metódy sú opcionales - môžete používať postupne

## Testovacie coverage

### Unit testy implementované:
- ✅ [`AggregateTypeResolverTest`](tests/Unit/Infrastructure/Persistence/EventStore/AggregateTypeResolverTest.php) - derivácia aggregate_type
- ✅ [`CompoundPrimaryKeyTest`](tests/Unit/Infrastructure/Persistence/EventStore/CompoundPrimaryKeyTest.php) - compound PK funkcionalita

### Migrácie pripravené:
- ✅ [`Version20250727114900`](migrations/Version20250727114900.php) - aggregate_type pridanie
- ✅ [`Version20250727135000`](migrations/Version20250727135000.php) - compound primary key optimalizácia

## Next Steps pre Order doménu

1. **Spustite migrácie** pre optimalizácie
2. **Vytvorte Order events** v `src/Order/Domain/Event/`
3. **Používajte nové optimalizované metódy** pre lepší performance
4. **Definujte Order EventSerializer** a pridajte ho do `CompositeEventSerializer`
5. **Implementujte Order-specific query handlers** využívajúce domain separation

Event store je teraz **production-ready** s najlepšími ES praktikami a Order doména môže okamžite využívať všetky optimalizácie!