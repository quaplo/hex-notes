# Hexagonal Architecture (Ports & Adapters)

## Architectural Overview

Tento projekt implementuje hexagonálnu architektúru s jasne oddelenými vrstvami. Ako AI asistent musíš dodržiavať tieto pravidlá separácie.

## Layer Structure

### 🎯 Domain Layer (Core)
**Umiestnenie**: `src/{Context}/Domain/`
**Zodpovednosť**: Business logic a domain rules

```
src/User/Domain/
├── Model/           # Aggregates, Entities
├── ValueObject/     # Domain value objects  
├── Event/           # Domain events
├── Repository/      # Repository interfaces
└── Exception/       # Domain exceptions
```

#### Pravidlá Domain Layer:
- **ŽIADNE externe závislosti** (okrem PHP core)
- **Pure business logic** bez technical concerns
- **Immutable value objects**
- **Rich domain models** (nie anemic)

```php
// ✅ SPRÁVNE - Pure domain model
final class User extends AggregateRoot
{
    public function changeEmail(Email $newEmail): void
    {
        if ($this->email->equals($newEmail)) {
            return; // Business rule: no change needed
        }

        if (!$this->canChangeEmail()) {
            throw new UserInactiveException($this->id); // Domain exception
        }

        $this->email = $newEmail; // Domain logic
    }
}
```

### ⚙️ Application Layer (Use Cases)
**Umiestnenie**: `src/{Context}/Application/`
**Zodpovednosť**: Orchestration a koordinácia

```
src/User/Application/
├── Command/         # Command handlers (write)
├── Query/           # Query handlers (read)
├── EventHandler/    # Domain event handlers
└── Exception/       # Application exceptions
```

#### Pravidlá Application Layer:
- **Orchestruje domain objects**
- **Nezahŕňa business logic**
- **Používa repository interfaces**
- **Spracováva cross-cutting concerns**

```php
// ✅ SPRÁVNE - Application service orchestrates
final class DeleteUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private DomainEventDispatcherInterface $eventDispatcher
    ) {}

    public function __invoke(DeleteUserCommand $command): void
    {
        $user = $this->userRepository->findById($command->getUserId());
        if (!$user) {
            throw new UserNotFoundException($command->getUserId());
        }

        $user->delete(); // Domain method
        $this->userRepository->save($user); // Persistence
        $this->eventDispatcher->dispatch($user->getUncommittedEvents()); // Events
    }
}
```

### 🔧 Infrastructure Layer (Adapters)
**Umiestnenie**: `src/Infrastructure/` alebo `src/{Context}/Infrastructure/`
**Zodpovednosť**: External system integration

```
src/Infrastructure/
├── Http/            # Web controllers (input adapters)
├── Persistence/     # Database adapters (output adapters)
├── Event/           # Event system adapters
└── Bus/             # Message bus adapters

src/User/Infrastructure/
└── Persistence/
    └── Doctrine/    # Doctrine-specific implementations
```

#### Pravidlá Infrastructure Layer:
- **Implementuje repository interfaces**
- **Obsahuje framework-specific kód**
- **Adaptuje external systems**
- **Nemá business logic**

```php
// ✅ SPRÁVNE - Infrastructure adapter
final class UserRepository implements UserRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function save(User $user): void
    {
        $entity = $this->userMapper->toDoctrineEntity($user); // Mapping
        $this->entityManager->persist($entity); // Doctrine specific
        $this->entityManager->flush(); // Technical concern
    }
}
```

## Dependency Inversion

### Direction of Dependencies
```
Infrastructure → Application → Domain
     ↑              ↑           ↑
 Adapters      Use Cases    Business Logic
```

#### ✅ SPRÁVNE - Inward dependencies only
```php
// Domain defines interface
namespace App\User\Domain\Repository;
interface UserRepositoryInterface { }

// Application uses interface  
namespace App\User\Application\Command;
class DeleteUserHandler
{
    public function __construct(private UserRepositoryInterface $repo) {}
}

// Infrastructure implements interface
namespace App\User\Infrastructure\Persistence\Doctrine;
class UserRepository implements UserRepositoryInterface { }
```

#### ❌ NESPRÁVNE - Outward dependency
```php
// Domain depending on infrastructure - NIKDY!
namespace App\User\Domain\Model;
use Doctrine\ORM\Mapping as ORM; // ❌ ZAKÁZANÉ

#[ORM\Entity] // ❌ Domain nesmie závisieť od Doctrine
class User { }
```

## Ports & Adapters Pattern

### Input Ports (Use Cases)
**Umiestnenie**: `src/{Context}/Application/`

```php
// Port = Application service interface
interface DeleteUserUseCaseInterface
{
    public function execute(DeleteUserCommand $command): void;
}

// Implementation
final class DeleteUserHandler implements DeleteUserUseCaseInterface
{
    public function execute(DeleteUserCommand $command): void
    {
        // Use case logic
    }
}
```

### Input Adapters (Controllers)
**Umiestnenie**: `src/Infrastructure/Http/Controller/`

```php
// ✅ SPRÁVNE - Controller as adapter
final class UserController
{
    public function __construct(private DeleteUserUseCaseInterface $deleteUser) {}

    public function delete(Request $request): Response
    {
        $command = new DeleteUserCommand($request->get('id')); // HTTP → Domain
        $this->deleteUser->execute($command); // Delegate to use case
        return new Response('', 204); // Domain → HTTP
    }
}
```

### Output Ports (Repository Interfaces)
**Umiestnenie**: `src/{Context}/Domain/Repository/`

```php
// Port = Repository interface in domain
interface UserRepositoryInterface
{
    public function save(User $user): void;
    public function findById(Uuid $id): ?User;
}
```

### Output Adapters (Repository Implementations)
**Umiestnenie**: `src/{Context}/Infrastructure/Persistence/`

```php
// Adapter = Concrete implementation
final class DoctrineUserRepository implements UserRepositoryInterface
{
    // Doctrine-specific implementation
}
```

## Shared Kernel

### Čo patrí do Shared (`src/Shared/`)
- **Common Value Objects**: `Uuid`, `Email`
- **Base Classes**: `AggregateRoot`, `DomainEvent`
- **Cross-Domain Infrastructure**: `EventStore`, `DomainEventDispatcher`

### Čo NEPATRI do Shared
- **Domain-specific logic**
- **Business rules**
- **Aggregate-specific behavior**

```php
// ✅ SPRÁVNE - Generic value object
namespace App\Shared\ValueObject;
final class Uuid { }

// ❌ NESPRÁVNE - Domain-specific v shared
namespace App\Shared\Model;
final class UserProjectRelation { } // Patrí do konkrétnej domény
```

## Architectural Validation

### Pre každý nový súbor skontroluj:

1. **Layer Placement**: Je súbor v správnej vrstve?
2. **Dependencies**: Smerujú závislosti správne (dovnútra)?
3. **Responsibilities**: Má vrstva správnu zodpovednosť?
4. **Interfaces**: Sú definované porty v správnej vrstve?

### Red Flags (ZAKÁZANÉ):

❌ **Domain závisí od Infrastructure**
❌ **Application obsahuje business logic**
❌ **Infrastructure obsahuje business rules**
❌ **Cross-layer communication** (okrem definovaných portov)
❌ **Framework annotations v Domain**

## Testing Strategy

### Unit Tests
- **Domain**: Testuj business logic v izolácii
- **Application**: Mock repository interfaces
- **Infrastructure**: Integration testy s reálnymi adaptermi

### Architecture Tests
```php
// Validácia architektonických pravidiel
test('domain layer has no external dependencies')
    ->expect('App\User\Domain')
    ->not->toUse(['Doctrine', 'Symfony', 'Illuminate']);

test('infrastructure implements domain interfaces')
    ->expect('App\User\Infrastructure\Persistence\Doctrine\UserRepository')
    ->toImplement('App\User\Domain\Repository\UserRepositoryInterface');