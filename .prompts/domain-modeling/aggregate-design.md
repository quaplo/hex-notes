# Aggregate Design Patterns

## Aggregate Fundamentals

Agregáty sú kľúčovou konštrukciou v DDD. Ako AI asistent musíš porozumieť a správne implementovať aggregate boundaries a lifecycle.

## Aggregate Definition

### Core Principles
**Agregát je cluster súvisiacich objektov, ktoré sa spravujú ako jedna jednotka**

```php
// ✅ SPRÁVNE - User ako agregát root
final class User extends AggregateRoot
{
    private readonly Uuid $id;           // Identity
    private Email $email;                // Value object
    private UserStatus $status;          // Value object
    private readonly DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $deletedAt = null;

    // Aggregate invariants enforced here
    public function changeEmail(Email $newEmail): void
    {
        if (!$this->canChangeEmail()) {
            throw new UserInactiveException($this->id);
        }
        $this->email = $newEmail;
    }

    private function canChangeEmail(): bool
    {
        return $this->status->canPerformActions(); // Business rule
    }
}
```

### Aggregate Root Rules
**Len aggregate root môže byť referencovaný zvonka**

```php
// ✅ SPRÁVNE - Project ako aggregate root s vnútornými entities
final class Project extends AggregateRoot
{
    private Uuid $id;                    // Aggregate identity
    private ProjectName $name;
    private array $workers = [];         // Internal entities
    private Uuid $ownerId;               // Reference to other aggregate

    public function addWorker(ProjectWorker $worker): self
    {
        // Aggregate controls its internal state
        $this->ensureNotDeleted();
        $this->ensureWorkerNotExists($worker->getUserId());
        
        $project = clone $this;
        $project->workers[] = $worker;
        $project->recordEvent(new ProjectWorkerAddedEvent(
            $this->id,
            $worker->getUserId(),
            $worker->getRole(),
            $worker->getAddedBy()
        ));
        
        return $project;
    }

    private function ensureWorkerNotExists(Uuid $userId): void
    {
        foreach ($this->workers as $worker) {
            if ($worker->getUserId()->equals($userId)) {
                throw new ProjectWorkerAlreadyExistsException($this->id, $userId);
            }
        }
    }
}

// ✅ SPRÁVNE - ProjectWorker ako internal entity
final class ProjectWorker
{
    private function __construct(
        private readonly Uuid $userId,      // Reference to User aggregate
        private readonly ProjectRole $role,
        private readonly ?Uuid $addedBy,
        private readonly DateTimeImmutable $addedAt
    ) {}

    public static function create(
        Uuid $userId,
        ProjectRole $role,
        ?Uuid $addedBy = null,
        ?DateTimeImmutable $addedAt = null
    ): self {
        return new self($userId, $role, $addedBy, $addedAt ?? new DateTimeImmutable());
    }

    // No direct persistence - only through Project aggregate
    public function getUserId(): Uuid { return $this->userId; }
    public function getRole(): ProjectRole { return $this->role; }
}
```

## Aggregate Boundaries

### Single Aggregate Rules
**Aggregate boundary určuje čo patrí spolu**

```php
// ✅ SPRÁVNE - Clear aggregate boundaries
// User aggregate: user identity, email, status
final class User extends AggregateRoot
{
    // User-specific data only
    private readonly Uuid $id;
    private Email $email;
    private UserStatus $status;
}

// Project aggregate: project data, workers, metadata
final class Project extends AggregateRoot
{
    // Project-specific data only
    private Uuid $id;
    private ProjectName $name;
    private array $workers = [];  // ProjectWorker entities
    private Uuid $ownerId;        // Reference to User aggregate
}

// ❌ NESPRÁVNE - Mixed boundaries
final class User extends AggregateRoot
{
    private array $projects = [];     // ❌ Project data in User aggregate
    private array $notifications = [];// ❌ Different aggregate concept
    private UserPreferences $prefs;   // ❌ Possibly separate aggregate
}
```

### Cross-Aggregate References
**Používaj IDs pre referencie medzi agregátmi**

```php
// ✅ SPRÁVNE - Reference by ID
final class Project extends AggregateRoot
{
    private Uuid $ownerId;  // Reference to User by ID only

    public function isOwnedBy(Uuid $userId): bool
    {
        return $this->ownerId->equals($userId);
    }

    public function changeOwner(Uuid $newOwnerId): self
    {
        $project = clone $this;
        $project->ownerId = $newOwnerId;
        $project->recordEvent(new ProjectOwnerChangedEvent(
            $this->id,
            $this->ownerId,  // old owner
            $newOwnerId      // new owner
        ));
        return $project;
    }
}

// ❌ NESPRÁVNE - Direct object reference
final class Project extends AggregateRoot
{
    private User $owner;  // ❌ Direct reference to other aggregate

    public function changeOwner(User $newOwner): self
    {
        // ❌ This creates coupling between aggregates
        $this->owner = $newOwner;
        return $this;
    }
}
```

## Transaction Boundaries

### One Aggregate Per Transaction
**Každý agregát je transaction boundary**

```php
// ✅ SPRÁVNE - Single aggregate modification
final class DeleteUserHandler
{
    public function __invoke(DeleteUserCommand $command): void
    {
        // Transaction boundary = User aggregate only
        $user = $this->userRepository->findById($command->getUserId());
        if (!$user) {
            throw new UserNotFoundException($command->getUserId());
        }

        $user->delete();  // Modifies only User aggregate
        $this->userRepository->save($user);
        
        // Cross-aggregate coordination via events
        $this->eventDispatcher->dispatch($user->getUncommittedEvents());
    }
}

// Cross-aggregate reaction via event handler
final class UserDeletedEventHandler
{
    public function __invoke(UserDeletedEvent $event): void
    {
        // Separate transaction for Project aggregate
        $command = new DeleteOrphanedProjectsCommand($event->getUserId());
        $this->commandBus->dispatch($command);
    }
}

// ❌ NESPRÁVNE - Multiple aggregates in one transaction
final class DeleteUserHandler
{
    public function __invoke(DeleteUserCommand $command): void
    {
        $user = $this->userRepository->findById($command->getUserId());
        $projects = $this->projectRepository->findByOwner($command->getUserId());
        
        $user->delete();           // ❌ Multiple aggregates
        foreach ($projects as $project) {
            $project->delete();    // ❌ in single transaction
        }
        
        $this->userRepository->save($user);
        $this->projectRepository->saveAll($projects); // ❌ Cross-aggregate transaction
    }
}
```

## Aggregate Lifecycle

### Creation Patterns
**Agregáty sa vytvárajú cez factory methods**

```php
// ✅ SPRÁVNE - Static factory methods
final class User extends AggregateRoot
{
    private function __construct(
        private readonly Uuid $id,
        private Email $email,
        private UserStatus $status,
        private readonly DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $deletedAt = null
    ) {}

    public static function register(Email $email): self
    {
        return new self(
            Uuid::generate(),
            $email,
            UserStatus::ACTIVE,
            new DateTimeImmutable()
        );
    }

    public static function fromPrimitives(
        string $id,
        string $email,
        string $status,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $deletedAt = null
    ): self {
        return new self(
            Uuid::create($id),
            Email::fromString($email),
            UserStatus::from($status),
            $createdAt,
            $deletedAt
        );
    }
}

final class Project extends AggregateRoot
{
    public static function create(ProjectName $name, Uuid $ownerId): self
    {
        $project = new self(Uuid::generate(), $name, new DateTimeImmutable(), $ownerId);
        $project->recordEvent(new ProjectCreatedEvent($project->getId(), $name, $ownerId));
        return $project;
    }
}
```

### Modification Patterns
**Immutable modifications s return novej inštancie**

```php
// ✅ SPRÁVNE - Immutable modifications
final class Project extends AggregateRoot
{
    public function rename(ProjectName $newName): self
    {
        if ($this->isDeleted()) {
            throw new \DomainException('Cannot rename deleted project');
        }

        if ($this->name->equals($newName)) {
            return $this; // No change needed
        }

        // Return new instance
        $project = new self(
            $this->id,
            $newName,
            $this->createdAt,
            $this->ownerId,
            $this->deletedAt
        );

        $project->workers = $this->workers;
        $project->setVersion($this->getVersion());
        $project->recordEvent(new ProjectRenamedEvent(
            $this->id,
            $this->name,  // old name
            $newName      // new name
        ));

        return $project;
    }

    public function addWorker(ProjectWorker $worker): self
    {
        // Validation
        $this->ensureNotDeleted();
        $this->ensureWorkerNotExists($worker->getUserId());

        // Create new instance
        $project = clone $this;
        $project->workers[] = $worker;
        $project->recordEvent(new ProjectWorkerAddedEvent(
            $this->id,
            $worker->getUserId(),
            $worker->getRole(),
            $worker->getAddedBy()
        ));

        return $project;
    }
}
```

## Invariant Enforcement

### Business Rules Protection
**Agregát chráni svoje business invariants**

```php
// ✅ SPRÁVNE - Strong invariant protection
final class User extends AggregateRoot
{
    public function changeEmail(Email $newEmail): void
    {
        // Invariant: Only active users can change email
        if (!$this->status->canPerformActions()) {
            throw new UserInactiveException($this->id);
        }

        // Invariant: Email must be different
        if ($this->email->equals($newEmail)) {
            return; // No change needed
        }

        $this->email = $newEmail;
    }

    public function suspend(): void
    {
        // Invariant: Cannot suspend already deleted user
        if ($this->isDeleted()) {
            throw new \DomainException('Cannot suspend deleted user');
        }

        $this->status = UserStatus::SUSPENDED;
    }
}

final class Project extends AggregateRoot
{
    public function addWorker(ProjectWorker $worker): self
    {
        // Invariant: Project must not be deleted
        $this->ensureNotDeleted();
        
        // Invariant: Worker must not already exist
        $this->ensureWorkerNotExists($worker->getUserId());
        
        // Invariant: Check maximum workers limit
        $this->ensureWorkerLimitNotExceeded();

        $project = clone $this;
        $project->workers[] = $worker;
        $project->recordEvent(new ProjectWorkerAddedEvent(/*...*/));
        
        return $project;
    }

    private function ensureWorkerLimitNotExceeded(): void
    {
        if (count($this->workers) >= self::MAX_WORKERS) {
            throw new ProjectWorkerLimitExceededException($this->id);
        }
    }
}
```

## Size Guidelines

### Aggregate Size Rules
**Držte agregáty malé a fokusované**

```php
// ✅ SPRÁVNE - Small, focused aggregate
final class User extends AggregateRoot
{
    // 4-5 properties, clear responsibility
    private readonly Uuid $id;
    private Email $email;
    private UserStatus $status;
    private readonly DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $deletedAt = null;

    // 8-10 methods, all user-related
    public function changeEmail(Email $newEmail): void { }
    public function activate(): void { }
    public function suspend(): void { }
    public function delete(): void { }
    public function isActive(): bool { }
    public function canPerformActions(): bool { }
}

// ✅ SPRÁVNE - Moderately sized aggregate
final class Project extends AggregateRoot
{
    // Core properties + collection
    private Uuid $id;
    private ProjectName $name;
    private array $workers = [];
    private Uuid $ownerId;
    private ?DateTimeImmutable $deletedAt = null;

    // Methods focused on project management
    public function rename(ProjectName $newName): self { }
    public function addWorker(ProjectWorker $worker): self { }
    public function removeWorker(Uuid $workerId): self { }
    public function delete(): self { }
    public function isOwnedBy(Uuid $userId): bool { }
}

// ❌ NESPRÁVNE - Too large aggregate
final class User extends AggregateRoot
{
    // Too many concerns
    private Uuid $id;
    private Email $email;
    private UserProfile $profile;         // ❌ Separate aggregate?
    private array $projects = [];         // ❌ Different aggregate
    private array $notifications = [];    // ❌ Different aggregate
    private UserSettings $settings;       // ❌ Separate aggregate?
    private array $sessions = [];         // ❌ Technical concern
    private UserStatistics $stats;        // ❌ Read model concern
    
    // Too many methods with mixed responsibilities
    public function createProject(): void { }      // ❌ Project responsibility
    public function sendNotification(): void { }   // ❌ Notification responsibility
    public function updateStatistics(): void { }   // ❌ Statistics responsibility
}
```

## Event Sourcing Considerations

### Event Sourcing Aggregate Design
**Agregáty v Event Sourcing majú špeciálne požiadavky**

```php
// ✅ SPRÁVNE - Event Sourcing aggregate
final class Project extends AggregateRoot
{
    public static function createEmpty(): self
    {
        // Empty state for event replay
        return new self(
            Uuid::create('00000000-0000-0000-0000-000000000000'),
            new ProjectName('__EMPTY__'),
            new DateTimeImmutable('1970-01-01T00:00:00+00:00'),
            Uuid::create('00000000-0000-0000-0000-000000000000')
        );
    }

    protected function handleEvent(DomainEvent $event): void
    {
        // Event replay logic
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
        // Reconstruct state from event
        $this->id = $event->getProjectId();
        $this->name = $event->getName();
        $this->createdAt = $event->getOccurredAt();
        $this->ownerId = $event->getOwnerId();
    }
}
```

## Aggregate Design Checklist

Pri návrhu agregátu sa spýtaj:

- [ ] **Je agregát malý a fokusovaný?** Má jednu jasná zodpovednosť?
- [ ] **Sú business invariants chránené?** Nemôže sa dostať do nekonzistentného stavu?
- [ ] **Je transaction boundary správne?** Jeden agregát = jedna transakcia?
- [ ] **Používajú sa ID referencie?** Žiadne priame referencie na iné agregáty?
- [ ] **Má jasný lifecycle?** Creation, modification, deletion patterns?
- [ ] **Podporuje Event Sourcing?** Replay schopnosť?
- [ ] **Sú entities správne vnorené?** Pristupné len cez aggregate root?

## Anti-Patterns

### ❌ Common Aggregate Anti-Patterns

1. **Anemic Aggregates** - len getters/setters bez business logic
2. **God Aggregates** - príliš veľké s multiple responsibilities  
3. **Cross-Aggregate Transactions** - modifikácia multiple aggregates naraz
4. **Direct Entity Access** - pristupovanie k vnútorným entities zvonka
5. **Missing Invariants** - nechránené business rules
6. **Mutable Shared State** - shared objects medzi agregátmi

### Refactoring Guidelines

```php
// ❌ BEFORE - Anemic aggregate
final class User extends AggregateRoot
{
    public function setEmail(string $email): void
    {
        $this->email = $email; // ❌ No business logic
    }
    
    public function setStatus(string $status): void
    {
        $this->status = $status; // ❌ No validation
    }
}

// ✅ AFTER - Rich aggregate
final class User extends AggregateRoot
{
    public function changeEmail(Email $newEmail): void
    {
        if (!$this->canChangeEmail()) {
            throw new UserInactiveException($this->id);
        }
        
        if ($this->email->equals($newEmail)) {
            return; // No change needed
        }
        
        $this->email = $newEmail;
        $this->recordEvent(new UserEmailChangedEvent($this->id, $newEmail));
    }
    
    public function suspend(): void
    {
        if ($this->isDeleted()) {
            throw new \DomainException('Cannot suspend deleted user');
        }
        
        $this->status = UserStatus::SUSPENDED;
        $this->recordEvent(new UserSuspendedEvent($this->id));
    }
}