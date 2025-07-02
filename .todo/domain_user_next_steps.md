# Analýza User domény - Návrh zlepšení DDD/Hexagonálnej architektúry

## 🚨 Kritické problémy

### 1. UserService ako aplikačná služba obsahuje business logic

**Problém**: [`UserService`](../src/User/Application/UserService.php:1) obsahuje business logic, ktorá má byť v domain layer
```php
// ❌ Business logic v application layer
public function createUser(Email $email): User {
    $exist = $this->userRepository->findByEmail($email);
    if ($exist) {
        throw new EmailAlreadyExistsException('Email already exists');
    }
    $user = User::create($email);
    $this->userRepository->save($user);
    return $user;
}
```

**Riešenie**: Presunúť business logic do domain modelu a používať command pattern
```php
// ✅ Cielové riešenie
class CreateUserHandler {
    public function __invoke(CreateUserCommand $command): User {
        $existingUser = $this->userRepository->findByEmail($command->email);
        if ($existingUser) {
            throw new UserAlreadyExistsException($command->email);
        }
        
        $user = User::register($command->email); // Domain method
        $this->userRepository->save($user);
        return $user;
    }
}
```

### 2. Anemic Domain Model

**Problém**: [`User`](../src/User/Domain/Model/User.php:1) je len data holder s getters, chýbajú business operations
```php
// ❌ Anemic model - len getters
final readonly class User {
    public function getId(): Uuid { return $this->id; }
    public function getEmail(): Email { return $this->email; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
}
```

**Riešenie**: Rich domain model s business operáciami (bez event sourcing)
```php
// ✅ Rich domain model pre ORM
final class User {
    private UserStatus $status = UserStatus::ACTIVE;
    
    public function changeEmail(Email $newEmail): void {
        if ($this->email->equals($newEmail)) {
            return; // No change needed
        }
        
        if (!$this->canChangeEmail()) {
            throw new UserInactiveException($this->id);
        }
        
        $this->email = $newEmail;
    }
    
    public function activate(): void {
        $this->status = UserStatus::ACTIVE;
    }
    
    public function deactivate(): void {
        $this->status = UserStatus::INACTIVE;
    }
    
    public function isActive(): bool {
        return $this->status === UserStatus::ACTIVE;
    }
    
    public function canChangeEmail(): bool {
        return $this->isActive();
    }
}
```

## 📋 Stredne kritické problémy

### 4. Command/Query handlers závisia od UserService

**Problém**: Handlers používajú [`UserService`](../src/User/Application/UserService.php:1) namiesto direct repository access
```php
// ❌ Handler závisí od application service
class CreateUserHandler {
    public function __construct(private UserService $userService) {}
    
    public function __invoke(CreateUserCommand $command): User {
        return $this->userService->createUser($command->getEmail());
    }
}
```

**Riešenie**: Direct repository dependency s domain interface
```php
// ✅ Konzistentný pattern ako v Project doméne
class CreateUserHandler {
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}
    
    public function __invoke(CreateUserCommand $command): User {
        // validation + domain operations + repository
    }
}
```

### 5. Nekonzistentné exception handling

**Problém**: Miešajú sa application a domain exceptions
```php
// ❌ Application exception pre domain rule
throw new EmailAlreadyExistsException('Email already exists');
```

**Riešenie**: Špecifické domain exceptions
```php
// ✅ Domain exceptions
throw new UserAlreadyExistsException($email);
throw new UserNotFoundException($userId);
throw new UserInactiveException($userId);
```

### 6. Chýba Command/Query Bus integration

**Problém**: [`UserController`](../src/Infrastructure/Http/Controller/UserController.php:1) používa direct handler dependencies
```php
// ❌ Direct handler dependencies
public function __construct(
    private readonly CreateUserHandler $createUserHandler,
    private readonly GetUserByIdHandler $getUserByIdHandler,
    private readonly SerializerInterface $serializer,
) {}
```

**Riešenie**: Command/Query Bus pattern ako v ProjectController
```php
// ✅ Bus pattern
public function __construct(
    private readonly CommandBus $commandBus,
    private readonly QueryBus $queryBus,
    private readonly SerializerInterface $serializer,
) {}
```

## 🔧 Menšie vylepšenia

### 7. Doctrine ORM optimalizácia

**Problém**: [`UserRepository`](../src/User/Infrastructure/Persistence/Doctrine/UserRepository.php:19) volá flush() pri každom save()
```php
// ❌ Immediate flush
public function save(User $user): void {
    $entity = new UserEntity(/*...*/);
    $this->em->persist($entity);
    $this->em->flush(); // Expensive operation
}
```

**Riešenie**: Unit of Work pattern alebo batch operations
```php
// ✅ Lepšie riešenie
public function save(User $user): void {
    $entity = new UserEntity(/*...*/);
    $this->em->persist($entity);
    // Flush bude volaný v transaction middleware alebo explicitly
}
```

### 8. Chýbajúce Value Objects

**Problém**: User má len Email, ale chýbajú ďalšie domain value objects
```php
// ❌ Primitives v domain model
final readonly class User {
    private function __construct(
        private Uuid $id,
        private Email $email,
        private DateTimeImmutable $createdAt  // Missing UserStatus, UserProfile, etc.
    ) {}
}
```

**Riešenie**: Comprehensive value objects
```php
// ✅ Rich value objects
final class User extends AggregateRoot {
    private function __construct(
        private Uuid $id,
        private Email $email,
        private UserStatus $status,
        private UserProfile $profile,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $lastLoginAt = null
    ) {}
}
```

### 9. Chýbajúca testovacia infrastruktúra

**Problém**: User doména nemá tests ako Project doména

**Riešenie**: Kompletná testovacia infrastruktúra
- Unit tests pre User domain model
- Integration tests pre repository
- Command/Query handler tests
- End-to-end tests

## 📋 Implementačný plán (priorita)

### ✅ Vysoká priorita - DOKONČENÉ
1. ✅ **Rich Domain Model** - implementované business metódy `changeEmail()`, `activate()`, `deactivate()` + [`UserStatus`](../src/User/Domain/ValueObject/UserStatus.php:1) enum
2. ✅ **Domain exceptions** - [`UserAlreadyExistsException`](../src/User/Domain/Exception/UserAlreadyExistsException.php:1), [`UserNotFoundException`](../src/User/Domain/Exception/UserNotFoundException.php:1), [`UserInactiveException`](../src/User/Domain/Exception/UserInactiveException.php:1)
3. ✅ **UserService odstránený** - [`CreateUserHandler`](../src/User/Application/Command/CreateUserHandler.php:1) a query handlers používajú direct repository access
4. ✅ **Command/Query Bus integrácia** - [`UserController`](../src/Infrastructure/Http/Controller/UserController.php:1) používa bus pattern, handlers zaregistrované v [`services.yaml`](../config/services.yaml:49)

### Stredná priorita
6. **Vytvoriť Value Objects** - `UserStatus`, `UserProfile`, `UserPermissions`
7. **Rozšíriť business operations** - `changeEmail()`, `activate()`, `deactivate()`, `updateProfile()`
8. **Optimalizovať Doctrine integration** - batch operations, proper mapping
9. **Implementovať domain services** - pre komplexné business rules (napr. user uniqueness check)

### Nízka priorita
10. **Pridať integration events** - pre komunikáciu s inými bounded contexts
11. **Implementovať user permissions/roles** - authorization domain logic
12. **Pridať audit trail** - tracking zmien v user profile

## 🏗️ Cielová architektúra User domény

```
User/
├── Domain/
│   ├── Model/
│   │   ├── User.php (rich domain model s business logic)
│   │   └── UserStatus.php (enum)
│   ├── ValueObject/
│   │   ├── UserProfile.php
│   │   └── UserPermissions.php
│   ├── Exception/
│   │   ├── UserAlreadyExistsException.php
│   │   ├── UserNotFoundException.php
│   │   └── UserInactiveException.php
│   ├── Repository/
│   │   └── UserRepositoryInterface.php
│   └── Service/
│       └── UserUniquenessService.php (domain service)
├── Application/
│   ├── Command/
│   │   ├── RegisterUserCommand.php
│   │   ├── RegisterUserHandler.php
│   │   ├── ChangeUserEmailCommand.php
│   │   └── ChangeUserEmailHandler.php
│   └── Query/
│       ├── GetUserQuery.php
│       ├── GetUserHandler.php
│       ├── GetUsersListQuery.php
│       └── GetUsersListHandler.php
└── Infrastructure/
    └── Persistence/
        └── Doctrine/
            ├── UserRepository.php (implements domain interface)
            └── Entity/UserEntity.php (mapping)
```

## 🎯 Očakávané výsledky

Po implementácii týchto zmien:
- ✅ **Konzistentná DDD architektúra** (ORM-based approach)
- ✅ **Rich domain model** s business operations namiesto anemic model
- ✅ **Command/Query separation** s bus pattern
- ✅ **Špecifické domain exceptions** namiesto generických
- ✅ **Testovateľnosť** s kompletnou testovacou infraštruktúrou
- ✅ **Maintainability** vďaka clean architecture
- ✅ **Doctrine ORM optimalizácia** s proper domain modeling

## 🔄 Migračná stratégia

1. **Postupná refaktorizácia** - zachovať Doctrine ORM, postupne pridávať DDD elementy
2. **Backward compatibility** - zachovať API kontrakt počas refaktorizácie  
3. **Test-driven development** - implementovať testy pred refaktorizáciou
4. **Domain events ako addon** - pridať events bez breaking changes
5. **Command/Query Bus** - refaktorovať controller až po stabilizácii command/query handlerov

## 📊 Porovnanie s Project doménou

| Aspekt | Project doména | User doména | Akcija potrebná |
|--------|---------------|-------------|-----------------|
| Domain Events | ✅ Implementované | ❌ Chýbajú | Implementovať |
| AggregateRoot | ✅ Extends AggregateRoot | ❌ Anemic model | Refaktorovať |
| Command Handlers | ✅ Direct repository | ❌ Cez UserService | Refaktorovať |
| Domain Exceptions | ✅ Špecifické | ❌ Generické | Vytvoriť |
| Command/Query Bus | ✅ Implementované | ❌ Direct dependencies | Integrovať |
| Value Objects | ✅ Rich VOs | ❌ Len Email | Rozšíriť |
| Testing | ✅ 45 testov | ❌ Chýba | Implementovať |
| Business Logic | ✅ V domain model | ❌ V UserService | Presunúť |

---

*Tento dokument slúži ako roadmapa pre zlepšenie User domény v súlade s DDD a hexagonálnou architektúrou, pri zachovaní Doctrine ORM.*