# PHP Standards & Modern PHP Practices

## PHP Version & Language Features

Tento projekt používa **PHP 8.2+** s modernými language features. Ako AI asistent musíš využiť najnovšie PHP možnosti.

## Type System

### Strict Types
**Všetky súbory musia mať strict types**

```php
// ✅ SPRÁVNE - Vždy na začiatku súboru
<?php

declare(strict_types=1);

namespace App\User\Domain\Model;

final class User extends AggregateRoot
{
    // Implementation
}
```

### Type Declarations
**Používaj najšpecifickejšie typy**

```php
// ✅ SPRÁVNE - Specific types
final class User extends AggregateRoot
{
    private function __construct(
        private readonly Uuid $id,           // Value object type
        private Email $email,                // Value object type
        private UserStatus $status,          // Enum type
        private readonly DateTimeImmutable $createdAt, // Immutable type
        private ?DateTimeImmutable $deletedAt = null   // Nullable when appropriate
    ) {}

    public function changeEmail(Email $newEmail): void  // Void return type
    {
        // Implementation
    }

    public function getStatus(): UserStatus  // Enum return type
    {
        return $this->status;
    }

    /** @return DomainEvent[] */  // PHPDoc for array types
    public function getUncommittedEvents(): array
    {
        return $this->domainEvents;
    }
}

// ❌ NESPRÁVNE - Weak types
final class User extends AggregateRoot
{
    private function __construct(
        private $id,           // ❌ No type
        private string $email, // ❌ Primitive instead of value object
        private $status,       // ❌ No type
        private $createdAt     // ❌ No type
    ) {}

    public function changeEmail($newEmail)  // ❌ No parameter or return types
    {
        // Implementation
    }
}
```

### Union Types (PHP 8.0+)
**Používaj union types kde má zmysel**

```php
// ✅ SPRÁVNE - Union types for multiple valid types
final class EventSerializer
{
    public function serialize(DomainEvent|array $data): string
    {
        if ($data instanceof DomainEvent) {
            return json_encode($this->eventToArray($data));
        }
        
        return json_encode($data);
    }
}

// ✅ SPRÁVNE - Mixed type when truly anything is accepted
public function handleGenericData(mixed $data): void
{
    // Handle various data types
}
```

### Intersection Types (PHP 8.1+)
```php
// ✅ SPRÁVNE - Multiple interfaces
interface Timestampable
{
    public function getCreatedAt(): DateTimeImmutable;
}

interface Identifiable
{
    public function getId(): Uuid;
}

function processEntity(Timestampable&Identifiable $entity): void
{
    // Entity must implement both interfaces
}
```

## Properties & Methods

### Readonly Properties (PHP 8.1+)
**Používaj readonly pre immutable data**

```php
// ✅ SPRÁVNE - Readonly properties for immutability
final class UserCreatedEvent implements DomainEvent
{
    public function __construct(
        private readonly Uuid $userId,
        private readonly Email $email,
        private readonly DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {}

    public function getUserId(): Uuid
    {
        return $this->userId; // Cannot be modified
    }
}

// ✅ SPRÁVNE - Readonly class (PHP 8.2+)
readonly final class ProjectCreatedEvent implements DomainEvent
{
    public function __construct(
        public Uuid $projectId,
        public ProjectName $name,
        public Uuid $ownerId,
        public DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {}
}
```

### Property Promotion
**Používaj constructor property promotion**

```php
// ✅ SPRÁVNE - Constructor promotion
final class Email
{
    public function __construct(
        private readonly string $value
    ) {
        $this->validate($value);
    }

    private function validate(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException($email);
        }
    }
}

// ❌ NESPRÁVNE - Verbose traditional syntax
final class Email
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
        $this->validate($value);
    }
}
```

## Enums (PHP 8.1+)

### Backed Enums for Domain
**Používaj enums pre fixed domain values**

```php
// ✅ SPRÁVNE - String backed enum
enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case DELETED = 'deleted';

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canPerformActions(): bool
    {
        return match ($this) {
            self::ACTIVE => true,
            self::INACTIVE, self::SUSPENDED, self::DELETED => false,
        };
    }

    public static function fromString(string $status): self
    {
        return self::from($status);
    }
}

// ✅ SPRÁVNE - Int backed enum pre performance
enum ProjectRole: int
{
    case OWNER = 1;
    case ADMIN = 2;
    case DEVELOPER = 3;
    case VIEWER = 4;

    public function hasPermission(ProjectPermission $permission): bool
    {
        return match ($this) {
            self::OWNER => true,
            self::ADMIN => $permission !== ProjectPermission::DELETE_PROJECT,
            self::DEVELOPER => $permission->isBasicPermission(),
            self::VIEWER => $permission === ProjectPermission::VIEW_PROJECT,
        };
    }
}
```

## Match Expression (PHP 8.0+)

### Pattern Matching
**Používaj match namiesto switch kde je to vhodné**

```php
// ✅ SPRÁVNE - Match expression
protected function handleEvent(DomainEvent $event): void
{
    match (get_class($event)) {
        UserDeletedEvent::class => $this->handleUserDeleted($event),
        UserEmailChangedEvent::class => $this->handleEmailChanged($event),
        UserSuspendedEvent::class => $this->handleUserSuspended($event),
        default => throw new \RuntimeException('Unknown event type: ' . get_class($event))
    };
}

// ✅ SPRÁVNE - Match with values
public function calculateDiscount(UserType $userType, int $orderAmount): float
{
    return match ($userType) {
        UserType::PREMIUM => $orderAmount * 0.15,
        UserType::GOLD => $orderAmount * 0.10,
        UserType::SILVER => $orderAmount * 0.05,
        UserType::BASIC => 0.0,
    };
}

// ❌ NESPRÁVNE - Switch pre simple mapping
public function getUserTypeName(UserType $type): string
{
    switch ($type) {
        case UserType::PREMIUM:
            return 'Premium User';
        case UserType::GOLD:
            return 'Gold User';
        default:
            return 'Regular User';
    }
}

// ✅ SPRÁVNE - Match je lepšie
public function getUserTypeName(UserType $type): string
{
    return match ($type) {
        UserType::PREMIUM => 'Premium User',
        UserType::GOLD => 'Gold User',
        UserType::SILVER => 'Silver User',
        UserType::BASIC => 'Regular User',
    };
}
```

## Named Arguments (PHP 8.0+)

### Strategic Use of Named Arguments
**Používaj named arguments pre clarity**

```php
// ✅ SPRÁVNE - Named arguments pre clarity
$project = Project::create(
    name: new ProjectName('My Project'),
    ownerId: $userId
);

// ✅ SPRÁVNE - Especially useful with many parameters
$user = User::fromPrimitives(
    id: '550e8400-e29b-41d4-a716-446655440000',
    email: 'user@example.com',
    status: 'active',
    createdAt: new DateTimeImmutable(),
    deletedAt: null
);

// ✅ SPRÁVNE - Skip optional parameters
$query = new FindUsersQuery(
    status: UserStatus::ACTIVE,
    limit: 50
    // emailFilter and createdAfter are skipped
);
```

## Attributes (PHP 8.0+)

### Infrastructure Attributes
**Používaj attributes pre metadata v Infrastructure layer**

```php
// ✅ SPRÁVNE - Doctrine attributes (nie v Domain!)
namespace App\User\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
final class UserEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $email;

    #[ORM\Column(type: 'string', enumType: UserStatus::class)]
    private UserStatus $status;
}

// ✅ SPRÁVNE - Custom validation attributes
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Email
{
    public function __construct(
        public readonly string $message = 'Invalid email format'
    ) {}
}

// Usage
final class CreateUserRequest
{
    #[Email(message: 'Please provide valid email')]
    public string $email;
}
```

## Error Handling

### Exception Hierarchy
**Dobre navrhnutá exception hierarchy**

```php
// ✅ SPRÁVNE - Domain exception hierarchy
namespace App\User\Domain\Exception;

abstract class UserDomainException extends \DomainException
{
    protected function __construct(
        string $message,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}

final class UserNotFoundException extends UserDomainException
{
    public static function forId(Uuid $id): self
    {
        return new self("User with ID {$id->toString()} not found");
    }
}

final class UserInactiveException extends UserDomainException
{
    public static function forUser(Uuid $userId): self
    {
        return new self("User {$userId->toString()} is inactive");
    }
}

// ✅ SPRÁVNE - Application exception hierarchy
namespace App\User\Application\Exception;

abstract class UserApplicationException extends \RuntimeException
{
}

final class InvalidUserCommandException extends UserApplicationException
{
    public static function missingRequiredField(string $field): self
    {
        return new self("Required field '{$field}' is missing");
    }
}
```

## Modern PHP Features Usage

### Nullsafe Operator (PHP 8.0+)
```php
// ✅ SPRÁVNE - Nullsafe operator
public function getUserProjectCount(?User $user): int
{
    return $user?->getProjects()?->count() ?? 0;
}

// ❌ NESPRÁVNE - Verbose null checks
public function getUserProjectCount(?User $user): int
{
    if ($user === null) {
        return 0;
    }
    
    $projects = $user->getProjects();
    if ($projects === null) {
        return 0;
    }
    
    return $projects->count();
}
```

### First-class Callable Syntax (PHP 8.1+)
```php
// ✅ SPRÁVNE - First-class callable
$userIds = array_map(
    User::getId(...),  // Shorter syntax
    $users
);

// ✅ SPRÁVNE - Static method reference
$emails = array_filter(
    $emailStrings,
    Email::isValid(...)
);
```

### Array Unpacking in Arrays (PHP 7.4+)
```php
// ✅ SPRÁVNE - Spread operator in arrays
$allEvents = [
    ...$userEvents,
    ...$projectEvents,
    new SystemStartedEvent()
];

// ✅ SPRÁVNE - Merging arrays
$queryParams = [
    'limit' => 10,
    ...$additionalFilters,
    'orderBy' => 'created_at'
];
```

## Performance Considerations

### Opcache Optimization
**Optimalizuj pre produkčné prostredie**

```php
// ✅ SPRÁVNE - Use final classes for better opcache optimization
final class User extends AggregateRoot
{
    // Final classes can be better optimized
}

// ✅ SPRÁVNE - Prefer early returns
public function canPerformAction(Action $action): bool
{
    if ($this->isDeleted()) {
        return false;  // Early return
    }
    
    if ($this->isSuspended()) {
        return false;  // Early return
    }
    
    return $this->status->allowsAction($action);
}
```

### Memory Efficiency
```php
// ✅ SPRÁVNE - Use generators for large datasets
public function getAllUsers(): \Generator
{
    $offset = 0;
    $batchSize = 1000;
    
    do {
        $users = $this->repository->findBatch($offset, $batchSize);
        foreach ($users as $user) {
            yield $user;
        }
        $offset += $batchSize;
    } while (count($users) === $batchSize);
}

// ✅ SPRÁVNE - Unset large variables when done
public function processLargeDataset(array $data): void
{
    $processedData = $this->heavyProcessing($data);
    $this->save($processedData);
    
    unset($processedData); // Free memory
}
```

## Code Style & Formatting

### PSR-12 Compliance
**Dodržuj PSR-12 code style**

```php
// ✅ SPRÁVNE - PSR-12 formatting
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
            throw UserNotFoundException::forId($command->getUserId());
        }

        $user->delete();
        $this->userRepository->save($user);
        $this->eventDispatcher->dispatch($user->getUncommittedEvents());
    }
}
```

### Naming Conventions
```php
// ✅ SPRÁVNE - Clear, descriptive names
final class UserProjectCleanupService  // Class: PascalCase
{
    private const MAX_RETRY_ATTEMPTS = 3;  // Constant: UPPER_SNAKE_CASE
    
    private int $retryCount = 0;  // Property: camelCase
    
    public function cleanupOrphanedProjects(Uuid $userId): void  // Method: camelCase
    {
        $orphanedProjects = $this->findOrphanedProjects($userId);  // Variable: camelCase
        
        foreach ($orphanedProjects as $project) {
            $this->deleteProject($project);
        }
    }
}
```

## PHP Standards Checklist

Pri každom novom kóde:

- [ ] **Strict types** deklarované na začiatku súboru
- [ ] **Type hints** pre všetky parametre a return values
- [ ] **Readonly properties** pre immutable data
- [ ] **Final classes** kde inheritance nie je potrebná
- [ ] **Enums** namiesto konstánt pre fixed values
- [ ] **Match expressions** namiesto switch kde je to vhodné
- [ ] **Named arguments** pre lepšiu čitateľnosť
- [ ] **Exception hierarchy** pre doménové chyby
- [ ] **PSR-12** code style compliance
- [ ] **Modern PHP features** používané vhodne

## Static Analysis

### PHPStan Configuration
```php
// phpstan.neon
parameters:
    level: 9
    paths:
        - src
        - tests
    ignoreErrors:
        - '#Call to an undefined method.*#'
    checkMissingIterableValueType: true
    checkGenericClassInNonGenericObjectType: true
    reportUnmatchedIgnoredErrors: true
```

### Psalm Configuration
```xml
<!-- psalm.xml -->
<psalm
    totallyTyped="true"
    strictBinaryOperands="true"
    requireVoidReturnType="true"
    useAssertForType="true"
    level="1">
    <projectFiles>
        <directory name="src" />
        <ignoreFiles>
            <directory name="vendor" />
        </ignoreFiles>
    </projectFiles>
</psalm>