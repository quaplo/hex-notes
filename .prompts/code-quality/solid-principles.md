# SOLID Principles Implementation

## SOLID v kontexte DDD/Event Sourcing

Tento projekt striktne dodržiava SOLID princípy v rámci DDD architektúry. Ako AI asistent musíš zabezpečiť ich implementáciu.

## S - Single Responsibility Principle

### Domain Layer
**Každá trieda má jednu zodpovednosť**

```php
// ✅ SPRÁVNE - User má len user-related zodpovednosť
final class User extends AggregateRoot
{
    public function changeEmail(Email $newEmail): void { /* user logic */ }
    public function activate(): void { /* user logic */ }
    public function delete(): void { /* user logic */ }
    // Only user-related methods
}

// ✅ SPRÁVNE - Email value object má len email validáciu
final class Email
{
    public function __construct(private readonly string $value)
    {
        $this->validateEmail($value); // Single responsibility
    }
    
    private function validateEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException($email);
        }
    }
}

// ❌ NESPRÁVNE - User s mixed responsibilities
final class User extends AggregateRoot
{
    public function changeEmail(Email $newEmail): void { }
    public function sendEmailNotification(): void { } // ❌ Infrastructure concern
    public function calculateProjectStats(): array { } // ❌ Project domain logic
}
```

### Application Layer
**Command handlers majú jednu zodpovednosť**

```php
// ✅ SPRÁVNE - Handler pre jeden specific command
final class DeleteUserHandler
{
    public function __invoke(DeleteUserCommand $command): void
    {
        // Only handles user deletion
    }
}

// ❌ NESPRÁVNE - Handler s multiple responsibilities
final class UserManagementHandler
{
    public function createUser(CreateUserCommand $command): void { }
    public function deleteUser(DeleteUserCommand $command): void { }
    public function updateUser(UpdateUserCommand $command): void { } // ❌ Multiple responsibilities
}
```

## O - Open/Closed Principle

### Domain Events Extension
**Nové features cez nové events, nie modification**

```php
// ✅ SPRÁVNE - Extension cez nové events
abstract class AggregateRoot
{
    protected abstract function handleEvent(DomainEvent $event): void;
}

final class User extends AggregateRoot
{
    protected function handleEvent(DomainEvent $event): void
    {
        match (get_class($event)) {
            UserDeletedEvent::class => $this->handleUserDeleted($event),
            UserEmailChangedEvent::class => $this->handleEmailChanged($event), // ✅ New event added
            UserSuspendedEvent::class => $this->handleUserSuspended($event),   // ✅ New event added
            default => throw new \RuntimeException('Unknown event type: ' . get_class($event))
        };
    }
}

// ✅ SPRÁVNE - Nové domain services cez interfaces
interface UserNotificationServiceInterface
{
    public function notifyUserDeleted(User $user): void;
}

// Extensions
final class EmailNotificationService implements UserNotificationServiceInterface { }
final class SlackNotificationService implements UserNotificationServiceInterface { }
```

### Query Extensions
**Nové queries bez modification existujúcich**

```php
// ✅ SPRÁVNE - Base query interface
interface ProjectQueryInterface
{
    public function findById(Uuid $id): ?ProjectDetailDto;
}

// Extensions without modifying base
interface AdvancedProjectQueryInterface extends ProjectQueryInterface
{
    public function findWithStatistics(Uuid $id): ?ProjectWithStatsDto;
    public function findArchivedProjects(Uuid $ownerId): array;
}
```

## L - Liskov Substitution Principle

### Repository Implementations
**Všetky implementácie musia byť substitutable**

```php
// ✅ SPRÁVNE - Interface contract
interface UserRepositoryInterface
{
    public function save(User $user): void;
    public function findById(Uuid $id): ?User;
}

// ✅ SPRÁVNE - Implementácia dodržiava contract
final class DoctrineUserRepository implements UserRepositoryInterface
{
    public function save(User $user): void
    {
        // Doctrine implementation - honors contract
        $entity = $this->mapper->toDoctrineEntity($user);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
    
    public function findById(Uuid $id): ?User
    {
        // Returns User or null as contract specifies
        $entity = $this->entityManager->find(UserEntity::class, $id->toString());
        return $entity ? $this->mapper->toDomainModel($entity) : null;
    }
}

// ✅ SPRÁVNE - Alternative implementation
final class InMemoryUserRepository implements UserRepositoryInterface
{
    private array $users = [];
    
    public function save(User $user): void
    {
        $this->users[$user->getId()->toString()] = $user; // Same behavior
    }
    
    public function findById(Uuid $id): ?User
    {
        return $this->users[$id->toString()] ?? null; // Same contract
    }
}

// ❌ NESPRÁVNE - Breaks LSP
final class CachingUserRepository implements UserRepositoryInterface
{
    public function save(User $user): void
    {
        if ($user->isDeleted()) {
            throw new \Exception('Cannot save deleted user'); // ❌ Breaks contract
        }
    }
}
```

### Value Object Hierarchies
```php
// ✅ SPRÁVNE - Substitutable value objects
interface ValueObjectInterface
{
    public function equals(self $other): bool;
    public function toString(): string;
}

final class Email implements ValueObjectInterface
{
    public function equals(ValueObjectInterface $other): bool
    {
        return $other instanceof self && $this->value === $other->value;
    }
}

final class UserId implements ValueObjectInterface
{
    public function equals(ValueObjectInterface $other): bool
    {
        return $other instanceof self && $this->id->equals($other->id);
    }
}
```

## I - Interface Segregation Principle

### Špecifické Repository Interfaces
**Malé, špecializované interfaces**

```php
// ✅ SPRÁVNE - Segregated interfaces
interface UserFinderInterface
{
    public function findById(Uuid $id): ?User;
    public function findByEmail(Email $email): ?User;
}

interface UserPersisterInterface
{
    public function save(User $user): void;
    public function delete(Uuid $id): void;
}

interface UserSearchInterface
{
    public function searchByName(string $name): array;
    public function findByStatus(UserStatus $status): array;
}

// Clients depend only on what they need
final class DeleteUserHandler
{
    public function __construct(
        private UserFinderInterface $userFinder,      // ✅ Only needs finding
        private UserPersisterInterface $userPersister // ✅ Only needs persistence
    ) {}
}

// ❌ NESPRÁVNE - Fat interface
interface UserRepositoryInterface
{
    public function save(User $user): void;
    public function findById(Uuid $id): ?User;
    public function findByEmail(Email $email): ?User;
    public function searchByName(string $name): array;
    public function findByStatus(UserStatus $status): array;
    public function generateReport(): array;        // ❌ Not always needed
    public function sendBulkEmail(): void;         // ❌ Different responsibility
    public function exportToExcel(): string;      // ❌ Export concern
}
```

### Event Handler Interfaces
```php
// ✅ SPRÁVNE - Specific event handler interfaces
interface UserEventHandlerInterface
{
    public function handleUserDeleted(UserDeletedEvent $event): void;
}

interface ProjectEventHandlerInterface
{
    public function handleProjectCreated(ProjectCreatedEvent $event): void;
    public function handleProjectDeleted(ProjectDeletedEvent $event): void;
}

// ❌ NESPRÁVNE - Monolithic event handler
interface AllEventHandlerInterface
{
    public function handleUserDeleted(UserDeletedEvent $event): void;
    public function handleProjectCreated(ProjectCreatedEvent $event): void;
    public function handleEmailSent(EmailSentEvent $event): void;
    public function handlePaymentProcessed(PaymentEvent $event): void; // ❌ Different domain
}
```

## D - Dependency Inversion Principle

### Hexagonal Architecture Support
**High-level modules define interfaces**

```php
// ✅ SPRÁVNE - Domain defines interface
namespace App\User\Domain\Repository;
interface UserRepositoryInterface
{
    public function save(User $user): void;
    public function findById(Uuid $id): ?User;
}

// ✅ SPRÁVNE - Application uses domain interface
namespace App\User\Application\Command;
final class DeleteUserHandler
{
    public function __construct(
        private UserRepositoryInterface $repository // ✅ Depends on abstraction
    ) {}
}

// ✅ SPRÁVNE - Infrastructure implements domain interface
namespace App\User\Infrastructure\Persistence\Doctrine;
final class UserRepository implements UserRepositoryInterface
{
    // Implementation details
}
```

### Domain Services
```php
// ✅ SPRÁVNE - Domain defines service interface
namespace App\User\Domain\Service;
interface UserDomainServiceInterface
{
    public function isEmailUnique(Email $email): bool;
}

// ✅ SPRÁVNE - Application uses domain service
final class CreateUserHandler
{
    public function __construct(
        private UserDomainServiceInterface $domainService // ✅ Abstraction
    ) {}
}

// ✅ SPRÁVNE - Infrastructure implements
namespace App\User\Infrastructure\Service;
final class UserDomainService implements UserDomainServiceInterface
{
    public function __construct(private UserRepositoryInterface $repository) {}
    
    public function isEmailUnique(Email $email): bool
    {
        return $this->repository->findByEmail($email) === null;
    }
}
```

### Event Dispatching
```php
// ✅ SPRÁVNE - Domain defines dispatcher interface
namespace App\Shared\Domain\Event;
interface DomainEventDispatcherInterface
{
    public function dispatch(array $events): void;
}

// ✅ SPRÁVNE - Application depends on abstraction
final class DeleteUserHandler
{
    public function __construct(
        private DomainEventDispatcherInterface $eventDispatcher // ✅ Abstract dependency
    ) {}
}

// ✅ SPRÁVNE - Infrastructure provides implementation
namespace App\Shared\Infrastructure\Event;
final class SymfonyEventDispatcher implements DomainEventDispatcherInterface
{
    public function __construct(private EventDispatcherInterface $dispatcher) {}
    
    public function dispatch(array $events): void
    {
        foreach ($events as $event) {
            $this->dispatcher->dispatch($event);
        }
    }
}
```

## SOLID v Testing

### Testable Design
**SOLID principles enable easy testing**

```php
// ✅ SPRÁVNE - Easy to test thanks to DIP
final class DeleteUserHandlerTest extends TestCase
{
    public function test_deletes_user(): void
    {
        // Arrange - Mock dependencies thanks to interfaces
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $eventDispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        
        $user = User::register(Email::fromString('test@example.com'));
        $userRepository->method('findById')->willReturn($user);
        
        $handler = new DeleteUserHandler($userRepository, $eventDispatcher);
        
        // Act
        $handler(new DeleteUserCommand($user->getId()));
        
        // Assert
        $this->assertTrue($user->isDeleted());
    }
}
```

## SOLID Violations to Avoid

### ❌ Common Anti-Patterns

1. **God Objects** - triedy s príliš veľkou zodpovednosťou
2. **Tight Coupling** - direct dependencies na concrete classes
3. **Interface Pollution** - fat interfaces s unrelated methods
4. **Fragile Base Class** - inheritance hierarchies that break LSP
5. **Dependency Magnets** - triedy dependent na veľkom množstve services

### Refactoring Guidelines

1. **Split Responsibilities** - rozdeľ triedy s multiple concerns
2. **Extract Interfaces** - abstract dependencies
3. **Use Composition** - prefer composition over inheritance
4. **Create Specific Interfaces** - avoid fat interfaces
5. **Inject Dependencies** - use constructor injection

## SOLID Checklist

Pri každom novom kóde:

- [ ] **SRP**: Má trieda jedinú, jasne definovanú zodpovednosť?
- [ ] **OCP**: Môžem pridať nové features bez modification existing code?
- [ ] **LSP**: Sú všetky implementácie substitutable?
- [ ] **ISP**: Závisia clients len na interfaces ktoré potrebujú?
- [ ] **DIP**: Závisia high-level modules na abstractions, nie concretions?

## Architecture Enforcement

### PhpStan Rules
```php
// Custom rules for SOLID validation
final class SolidPrinciplesRule implements Rule
{
    public function getNodeType(): string
    {
        return ClassMethod::class;
    }
    
    public function processNode(Node $node, Scope $scope): array
    {
        // Check for SRP violations, DIP violations, etc.
    }
}
```

### Testing SOLID Compliance
```php
test('repositories depend only on domain interfaces')
    ->expect('App\User\Application')
    ->toOnlyUse([
        'App\User\Domain',
        'App\Shared\Domain',
        // Framework interfaces allowed
        'DateTimeImmutable'
    ]);

test('domain layer has no external dependencies')
    ->expect('App\User\Domain')
    ->not->toUse(['Doctrine', 'Symfony', 'Illuminate']);