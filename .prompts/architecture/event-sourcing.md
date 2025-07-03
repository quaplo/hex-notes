# Event Sourcing Architecture

## Event Sourcing Fundamentals

Tento projekt implementuje Event Sourcing pattern. Ako AI asistent musíš porozumieť a dodržiavať tieto princípy.

## Core Concepts

### Event Store
**Centrálne úložisko všetkých domain events**

```php
// Event Store interface
interface EventStoreInterface
{
    public function append(Uuid $aggregateId, array $events, int $expectedVersion): void;
    public function getEventsForAggregate(Uuid $aggregateId): array;
}
```

### Aggregate Root Implementation
**Každý agregát musí podporovať Event Sourcing**

```php
// ✅ SPRÁVNE - Base implementácia v AggregateRoot
abstract class AggregateRoot
{
    /** @var DomainEvent[] */
    private array $domainEvents = [];
    private int $version = 0;

    protected function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function replayEvent(DomainEvent $event): void
    {
        $this->handleEvent($event); // Reconstruct state
        $this->version++;
    }

    protected abstract function handleEvent(DomainEvent $event): void;
}
```

## Event Design Principles

### 1. Event Immutability
**Events sú immutable facts o tom čo sa stalo**

```php
// ✅ SPRÁVNE - Immutable event
final class UserDeletedEvent implements DomainEvent
{
    public function __construct(
        private readonly Uuid $userId,
        private readonly Email $email,
        private readonly DateTimeImmutable $occurredAt
    ) {}

    // Only getters, no setters
    public function getUserId(): Uuid { return $this->userId; }
    public function getEmail(): Email { return $this->email; }
    public function getOccurredAt(): DateTimeImmutable { return $this->occurredAt; }
}
```

### 2. Event Naming
**Používaj minulý čas - event opisuje čo sa už stalo**

```php
// ✅ SPRÁVNE
UserDeletedEvent
ProjectCreatedEvent
ProjectRenamedEvent
ProjectWorkerAddedEvent

// ❌ NESPRÁVNE
DeleteUserEvent      // Príkaz, nie event
UserDeletionEvent    // Abstraktný názov
CreateProjectEvent   // Prítomný čas
```

### 3. Event Content
**Event musí obsahovať všetky dáta potrebné pre replay**

```php
// ✅ SPRÁVNE - Complete event data
final class ProjectCreatedEvent implements DomainEvent
{
    public function __construct(
        private readonly Uuid $projectId,
        private readonly ProjectName $name,
        private readonly Uuid $ownerId,
        private readonly DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {}
}

// ❌ NESPRÁVNE - Incomplete event
final class ProjectCreatedEvent implements DomainEvent
{
    public function __construct(
        private readonly Uuid $projectId // Chýba name a ownerId!
    ) {}
}
```

## Aggregate Reconstruction

### Event Replay Pattern
**Agregáty sa rekonštruujú z events**

```php
// ✅ SPRÁVNE - Factory method pre Event Sourcing
final class Project extends AggregateRoot
{
    public static function createEmpty(): self
    {
        return new self(
            Uuid::create('00000000-0000-0000-0000-000000000000'), // Null UUID
            new ProjectName('__EMPTY__'),     // Placeholder - replaced by events
            new DateTimeImmutable('1970-01-01T00:00:00+00:00'), // Epoch time
            Uuid::create('00000000-0000-0000-0000-000000000000')  // Null owner
        );
    }

    protected function handleEvent(DomainEvent $event): void
    {
        match (get_class($event)) {
            ProjectCreatedEvent::class => $this->handleProjectCreated($event),
            ProjectRenamedEvent::class => $this->handleProjectRenamed($event),
            ProjectDeletedEvent::class => $this->handleProjectDeleted($event),
            ProjectWorkerAddedEvent::class => $this->handleProjectWorkerAdded($event),
            ProjectWorkerRemovedEvent::class => $this->handleProjectWorkerRemoved($event),
            default => throw new \RuntimeException('Unknown event type: ' . get_class($event))
        };
    }

    private function handleProjectCreated(ProjectCreatedEvent $event): void
    {
        $this->id = $event->getProjectId();
        $this->name = $event->getName();
        $this->createdAt = $event->getOccurredAt();
        $this->ownerId = $event->getOwnerId();
    }
}
```

### Replay Validation
**Každý event handler musí byť idempotentný**

```php
// ✅ SPRÁVNE - Idempotent event handling
private function handleProjectWorkerAdded(ProjectWorkerAddedEvent $event): void
{
    // Check if worker already exists (idempotency)
    foreach ($this->workers as $worker) {
        if ($worker->getUserId()->equals($event->getUserId())) {
            return; // Already added, skip
        }
    }
    
    $this->workers[] = ProjectWorker::create(
        $event->getUserId(),
        $event->getRole(),
        $event->getAddedBy(),
        $event->getOccurredAt()
    );
}
```

## Event Recording Rules

### 1. When to Record Events
**Record events pre business-significant changes**

```php
// ✅ SPRÁVNE - Business action triggers event
public function delete(): void
{
    if ($this->isDeleted()) {
        return; // Idempotent - no event needed
    }

    $this->status = UserStatus::DELETED;
    $this->deletedAt = new DateTimeImmutable();
    
    // Business event for cross-domain communication
    $this->recordEvent(UserDeletedEvent::create($this->id, $this->email));
}

// ❌ NESPRÁVNE - Technical events
public function setLastAccessTime(): void
{
    $this->lastAccess = new DateTimeImmutable();
    $this->recordEvent(new UserAccessedEvent()); // ❌ Technical, nie business
}
```

### 2. Event Ordering
**Events musia mať správne poradie**

```php
// ✅ SPRÁVNE - Chronological order matters
public function changeOwnerAndRename(Uuid $newOwnerId, ProjectName $newName): self
{
    $project = clone $this;
    
    // First change owner
    $project->ownerId = $newOwnerId;
    $project->recordEvent(new ProjectOwnerChangedEvent($this->id, $newOwnerId));
    
    // Then rename
    $project->name = $newName;
    $project->recordEvent(new ProjectRenamedEvent($this->id, $this->name, $newName));
    
    return $project;
}
```

### 3. Event Versioning
**Handle event schema evolution**

```php
// ✅ SPRÁVNE - Versioned events
final class ProjectCreatedEvent implements DomainEvent
{
    private const VERSION = 1;

    public function __construct(
        private readonly Uuid $projectId,
        private readonly ProjectName $name,
        private readonly Uuid $ownerId,
        private readonly DateTimeImmutable $occurredAt = new DateTimeImmutable(),
        private readonly int $version = self::VERSION
    ) {}

    public function getVersion(): int
    {
        return $this->version;
    }
}
```

## Snapshotting Strategy

### When to Snapshot
**Pre agregáty s veľkým počtom events**

```php
// Optional optimization - nie je implementované v základnej verzii
interface SnapshotableAggregate
{
    public function createSnapshot(): array;
    public static function fromSnapshot(array $data): self;
}
```

## Event Sourcing Best Practices

### DO's ✅

1. **Events sú immutable** - nikdy nemeň obsah eventu
2. **Complete events** - zahŕň všetky dáta potrebné pre replay
3. **Business language** - používaj doménové termíny
4. **Idempotent handlers** - replay môže byť opakovaný
5. **Version events** - priprav sa na schema evolution
6. **Async processing** - eventy môžu byť spracované neskôr

### DON'Ts ❌

1. **Nevymažávaj events** - sú trvalé historical record
2. **Nezahŕňaj technical details** - len business facts
3. **Nepoužívaj prítomný čas** - events sú o minulosti
4. **Neupravuj existujúce events** - vytvor nové verzie
5. **Nezávislí od implementačných detailov** - framework agnostic

## Cross-Domain Communication

### Event-Driven Integration
**Používaj events pre komunikáciu medzi bounded contexts**

```php
// ✅ SPRÁVNE - Cross-domain reaction
final class UserDeletedEventHandler
{
    public function __construct(
        private DeleteOrphanedProjectsUseCaseInterface $deleteProjects
    ) {}

    public function __invoke(UserDeletedEvent $event): void
    {
        // React to user deletion in Project domain
        $command = new DeleteOrphanedProjectsCommand($event->getUserId());
        $this->deleteProjects->execute($command);
    }
}
```

## Testing Event Sourcing

### Event-Based Testing
```php
// ✅ SPRÁVNE - Test events, nie state
test('user deletion records event')
    ->expect(function() {
        $user = User::register(Email::fromString('test@example.com'));
        $user->delete();
        
        return $user->getUncommittedEvents();
    })
    ->toHaveCount(1)
    ->and(fn($events) => $events[0])
    ->toBeInstanceOf(UserDeletedEvent::class);

// ✅ SPRÁVNE - Test replay
test('project can be reconstructed from events')
    ->expect(function() {
        $events = [
            new ProjectCreatedEvent($projectId, $name, $ownerId),
            new ProjectRenamedEvent($projectId, $oldName, $newName)
        ];
        
        $project = Project::createEmpty();
        foreach ($events as $event) {
            $project->replayEvent($event);
        }
        
        return $project->getName();
    })
    ->toBe($newName);
```

## Event Store Implementation

### Persistence Requirements
- **Append-only** storage
- **Optimistic concurrency** control
- **Event ordering** preservation
- **Query by aggregate ID**

```php
// Infrastructure implementation
final class DoctrineEventStore implements EventStoreInterface
{
    public function append(Uuid $aggregateId, array $events, int $expectedVersion): void
    {
        // Check version for optimistic locking
        $currentVersion = $this->getAggregateVersion($aggregateId);
        if ($currentVersion !== $expectedVersion) {
            throw new ConcurrencyException();
        }

        // Append events atomically
        foreach ($events as $event) {
            $this->persistEvent($aggregateId, $event, ++$currentVersion);
        }
    }
}