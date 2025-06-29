# Event Sourcing Implementation

Tento dokument vysvetľuje implementáciu Event Sourcing patternu v projekte Hex Notes.

## Čo je Event Sourcing?

Event Sourcing je architektúrny pattern, kde sa ukladajú všetky zmeny stavu aplikácie ako sekvencia events namiesto ukladania aktuálneho stavu. Každý event reprezentuje fakt, ktorý sa stal v systéme.

## Výhody Event Sourcingu

1. **Kompletná história**: Máme záznam o všetkých zmenách
2. **Audit trail**: Môžeme sledovať, kto a kedy urobil zmenu
3. **Debugging**: Môžeme rekonštruovať stav systému v akomkoľvek čase
4. **Temporal queries**: Môžeme sa pýtať na stav systému v minulosti
5. **Scalability**: Events môžeme spracovávať asynchronne

## Architektúra

### 1. Event Store
- `EventStore` interface definuje kontrakt pre ukladanie events
- `DoctrineEventStore` implementuje ukladanie do PostgreSQL
- Events sa ukladajú s verziou pre optimistic concurrency control

### 2. Aggregate Root
- `AggregateRoot` base class poskytuje funkcionalitu pre event sourcing
- Každý aggregate má verziu a metódy pre prácu s events
- `apply()` metóda aplikuje event na stav aggregate

### 3. Domain Events
- `ProjectCreatedEvent` - projekt bol vytvorený
- `ProjectRenamedEvent` - projekt bol premenovaný
- `ProjectDeletedEvent` - projekt bol vymazaný

### 4. Event Handlers
- `ProjectEventHandler` spracováva project events
- Môže posielať emaily, aktualizovať read modely, atď.

### 5. Event Dispatcher
- `EventDispatcher` interface a `SymfonyEventDispatcher` implementácia
- Spracováva events po úspešnom uložení do Event Store

## Použitie

### Vytvorenie projektu
```php
$project = $eventSourcingService->createProject('Môj projekt', 'user@example.com');
```

### Premenovanie projektu
```php
$project = $eventSourcingService->renameProject($projectId, 'Nový názov');
```

### Vymazanie projektu
```php
$project = $eventSourcingService->deleteProject($projectId);
```

### Získanie histórie
```php
$history = $eventSourcingService->getProjectHistory($projectId);
```

## Event Store Schema

```sql
CREATE TABLE event_store (
    id SERIAL PRIMARY KEY,
    aggregate_id VARCHAR(36) NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    event_data TEXT NOT NULL,
    version INTEGER NOT NULL,
    occurred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
);

CREATE INDEX idx_aggregate_version ON event_store (aggregate_id, version);
CREATE INDEX idx_occurred_at ON event_store (occurred_at);
CREATE UNIQUE INDEX idx_aggregate_version_unique ON event_store (aggregate_id, version);
```

## Event Flow

1. **Command** → Application Service
2. **Application Service** → Aggregate
3. **Aggregate** → Record Events
4. **Repository** → Save to Event Store
5. **Event Dispatcher** → Handle Events
6. **Event Handlers** → Side Effects (emails, notifications, etc.)

## Migrácia z tradičného prístupu

Ak chcete migrovať existujúce dáta do Event Sourcingu:

1. Vytvorte events pre existujúce dáta
2. Uložte ich do Event Store
3. Postupne presuňte funkcionalitu na Event Sourcing
4. Zachovajte read modely pre kompatibilitu

## Best Practices

1. **Events sú immutable** - nikdy nemeníme existujúce events
2. **Events sú facts** - reprezentujú to, čo sa stalo
3. **Aggregates sú konzistentné** - všetky business rules sa aplikujú v aggregate
4. **Event handlers sú idempotentné** - môžu sa spustiť viackrát bez problémov
5. **Používajte optimistic concurrency control** - verzie zabraňujú konfliktom

## Ďalšie kroky

1. Implementovať read modely/projections
2. Pridať event serialization/deserialization
3. Implementovať event replay
4. Pridať event versioning
5. Implementovať event sourcing pre User aggregate 