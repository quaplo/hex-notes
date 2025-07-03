# Domain Driven Design Principles

## Fundamentálne princípy DDD

Ako AI asistent pre tento projekt, musíš dodržiavať tieto DDD princípy:

### 1. Ubiquitous Language
- **VŽDY** používaj doménový jazyk z bounded contextu
- V `User` doméne: User, Email, UserStatus, UserDeletedEvent
- V `Project` doméne: Project, ProjectName, ProjectWorker, ProjectCreatedEvent
- **NIKDY** nemiešaj pojmy z rôznych domén
- Technické termíny používaj len v Infrastructure vrstve

### 2. Bounded Contexts
Tento projekt má jasne definované bounded contexts:

#### User Context (`src/User/`)
- **Zodpovednosť**: Správa používateľov a ich identity
- **Agregáty**: User
- **Key Entities**: User
- **Value Objects**: Email (shared), UserStatus
- **Events**: UserDeletedEvent

#### Project Context (`src/Project/`)
- **Zodpovednosť**: Správa projektov a ich členov
- **Agregáty**: Project
- **Key Entities**: Project
- **Value Objects**: ProjectName, ProjectWorker
- **Events**: ProjectCreatedEvent, ProjectDeletedEvent, ProjectRenamedEvent, ProjectWorkerAddedEvent, ProjectWorkerRemovedEvent

#### Shared Kernel (`src/Shared/`)
- **Zodpovednosť**: Spoločné koncepty a infraštruktúra
- **Value Objects**: Email, Uuid
- **Base Classes**: AggregateRoot, DomainEvent
- **Infrastructure**: EventStore, DomainEventDispatcher

### 3. Aggregate Design Rules

#### Aggregate Boundaries
```php
// ✅ SPRÁVNE - User je samostatný agregát
final class User extends AggregateRoot
{
    private readonly Uuid $id;
    private Email $email;
    private UserStatus $status;
    // ...
}

// ✅ SPRÁVNE - Project je samostatný agregát  
final class Project extends AggregateRoot
{
    private Uuid $id;
    private ProjectName $name;
    private array $workers = [];
    // ...
}
```

#### Aggregate Consistency
- **Jeden agregát = jedna transakcia**
- **Invarianty sa udržiavają len v rámci agregátu**
- **Cross-aggregate komunikácia iba cez Domain Events**

```php
// ✅ SPRÁVNE - Domain event pre cross-aggregate komunikáciu
public function delete(): void
{
    $this->status = UserStatus::DELETED;
    $this->deletedAt = new DateTimeImmutable();
    
    // Cross-domain communication via event
    $this->recordEvent(UserDeletedEvent::create($this->id, $this->email));
}
```

### 4. Domain Events Pattern

#### Event Naming Convention
- **Minulý čas**: `UserDeletedEvent`, `ProjectCreatedEvent`
- **Doménový jazyk**: používaj business terminológiu
- **Špecifickosť**: jasne opíš čo sa stalo

#### Event Design
```php
// ✅ SPRÁVNE - obsahuje všetky potrebné dáta
final class UserDeletedEvent implements DomainEvent
{
    public function __construct(
        private readonly Uuid $userId,
        private readonly Email $email,
        private readonly DateTimeImmutable $occurredAt
    ) {}
}
```

### 5. Value Objects Rules

#### Immutability
```php
// ✅ SPRÁVNE - Value object je immutable
final class Email
{
    private function __construct(private readonly string $value) {}
    
    public static function fromString(string $email): self
    {
        // validation logic
        return new self($email);
    }
    
    public function getValue(): string
    {
        return $this->value;
    }
}
```

#### Equality
```php
// ✅ SPRÁVNE - Value objects sa porovnávajú podľa hodnoty
public function equals(Email $other): bool
{
    return $this->value === $other->value;
}
```

### 6. Repository Pattern

#### Interface v Domain
```php
// ✅ SPRÁVNE - repository interface v domain layer
namespace App\User\Domain\Repository;

interface UserRepositoryInterface
{
    public function save(User $user): void;
    public function findById(Uuid $id): ?User;
}
```

#### Implementation v Infrastructure
```php
// ✅ SPRÁVNE - implementácia v infrastructure
namespace App\User\Infrastructure\Persistence\Doctrine;

final class UserRepository implements UserRepositoryInterface
{
    // Doctrine implementation
}
```

## Architectural Constraints

### POVINNÉ kontroly pri každom kóde:

1. **Dependency Direction**: Vždy smerom dovnútra (Domain ← Application ← Infrastructure)
2. **No Domain Dependencies**: Domain layer nemá závislosti na externých knižniciach
3. **Event Sourcing Support**: Všetky agregáty musia implementovať `handleEvent()`
4. **Immutability**: Value objects a Entity identifiers sú immutable
5. **Single Responsibility**: Jeden agregát = jedna zodpovednosť

### ZAKÁZANÉ patterns:

❌ **Domain Services v Application layer**
❌ **Priama komunikácia medzi agregátmi**
❌ **Anemic Domain Model**
❌ **Cross-aggregate transactions**
❌ **Infrastructure dependencies v Domain**

## Validácia DDD implementácie

Pred každým commitom skontroluj:
- [ ] Sú dodržané aggregate boundaries?
- [ ] Používa sa ubiquitous language?
- [ ] Sú domain eventy správne navrhnuté?
- [ ] Je zachovaná hexagonálna architektúra?
- [ ] Funguje Event Sourcing replay?