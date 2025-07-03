# Naming Conventions & Code Clarity

## Philosophy of Naming

Názvy v kóde musia byť **samodokumentujúce** a vyjadrovať **doménový jazyk**. Ako AI asistent musíš dbať na konzistentnosť názvov v celom projekte.

## Domain-Driven Naming

### Ubiquitous Language
**Používaj názvy z business domény**

```php
// ✅ SPRÁVNE - Business terminology
final class User extends AggregateRoot
{
    public function suspend(): void { }      // Business action
    public function activate(): void { }     // Business action
    public function delete(): void { }       // Business action
}

final class Project extends AggregateRoot
{
    public function addWorker(ProjectWorker $worker): self { }
    public function removeWorker(Uuid $workerId): self { }
    public function rename(ProjectName $newName): self { }
}

// ❌ NESPRÁVNE - Technical terminology
final class User extends AggregateRoot
{
    public function setStatus(int $status): void { }  // ❌ Generic technical term
    public function updateRecord(): void { }          // ❌ Database terminology
    public function disable(): void { }               // ❌ System terminology
}
```

### Contextual Naming
**Názvy musia byť špecifické pre kontext**

```php
// ✅ SPRÁVNE - Context-specific naming
namespace App\User\Domain\Event;
final class UserDeletedEvent implements DomainEvent { }

namespace App\Project\Domain\Event;
final class ProjectDeletedEvent implements DomainEvent { }

// ✅ SPRÁVNE - Different concepts in different contexts
namespace App\User\Domain\ValueObject;
final class UserStatus { }  // User-specific status

namespace App\Project\Domain\ValueObject;
final class ProjectStatus { }  // Project-specific status

// ❌ NESPRÁVNE - Generic naming
final class DeletedEvent { }     // ❌ Čo bolo zmazané?
final class Status { }           // ❌ Status čoho?
final class GenericException { } // ❌ Príliš všeobecné
```

## Class Naming

### Entity & Aggregate Naming
**Použite business pojmy pre entity**

```php
// ✅ SPRÁVNE - Clear business entities
final class User extends AggregateRoot { }
final class Project extends AggregateRoot { }
final class ProjectWorker { }  // Clear role in domain

// ✅ SPRÁVNE - Domain-specific entities
final class UserAccount { }     // If different from User
final class ProjectMembership { } // If complex relationship
final class UserSession { }     // Session management entity
```

### Value Object Naming
**Hodnoty opisujú vlastnosti alebo charakteristiky**

```php
// ✅ SPRÁVNE - Descriptive value objects
final class Email { }
final class ProjectName { }
final class UserStatus { }
final class ProjectRole { }
final class CreatedAt { }

// ✅ SPRÁVNE - Composite value objects
final class UserCredentials
{
    public function __construct(
        private readonly Email $email,
        private readonly HashedPassword $password
    ) {}
}

final class ProjectSettings
{
    public function __construct(
        private readonly bool $isPublic,
        private readonly int $maxWorkers,
        private readonly ProjectVisibility $visibility
    ) {}
}

// ❌ NESPRÁVNE - Technical or unclear names
final class UserData { }      // ❌ Čo obsahuje?
final class ProjectInfo { }   // ❌ Aké informácie?
final class Config { }        // ❌ Config čoho?
```

### Service & Handler Naming

#### Command Handlers
```php
// ✅ SPRÁVNE - Clear action-based naming
final class CreateUserHandler { }
final class DeleteUserHandler { }
final class ChangeUserEmailHandler { }
final class SuspendUserHandler { }

final class CreateProjectHandler { }
final class RenameProjectHandler { }
final class AddWorkerToProjectHandler { }

// ❌ NESPRÁVNE - Generic or unclear
final class UserHandler { }        // ❌ Čo robí s userom?
final class ProjectService { }     // ❌ Aký service?
final class UserManager { }        // ❌ Manager pattern
```

#### Query Handlers
```php
// ✅ SPRÁVNE - Descriptive query names
final class FindUserByIdHandler { }
final class FindUserByEmailHandler { }
final class FindActiveUsersHandler { }

final class FindProjectsByOwnerHandler { }
final class FindProjectWithWorkersHandler { }
final class GetProjectStatisticsHandler { }

// ❌ NESPRÁVNE - Unclear purpose
final class UserReader { }         // ❌ Reads what exactly?
final class ProjectFinder { }      // ❌ Finds what?
final class DataRetriever { }      // ❌ Retrieves what data?
```

#### Domain Services
```php
// ✅ SPRÁVNE - Business-focused services
final class UserUniquenessChecker { }
final class ProjectPermissionService { }
final class EmailDuplicationValidator { }

// ❌ NESPRÁVNE - Technical focus
final class UserValidator { }      // ❌ Validuje čo?
final class DatabaseChecker { }    // ❌ Technical concern
final class DataService { }        // ❌ Vague purpose
```

## Method Naming

### Business Actions
**Metódy musia vyjadrovať business intent**

```php
// ✅ SPRÁVNE - Clear business actions
final class User extends AggregateRoot
{
    public function register(Email $email): self { }
    public function changeEmail(Email $newEmail): void { }
    public function suspend(string $reason): void { }
    public function activate(): void { }
    public function delete(): void { }
}

final class Project extends AggregateRoot
{
    public function create(ProjectName $name, Uuid $ownerId): self { }
    public function rename(ProjectName $newName): self { }
    public function addWorker(ProjectWorker $worker): self { }
    public function removeWorker(Uuid $workerId): self { }
    public function archive(): self { }
}

// ❌ NESPRÁVNE - Technical or unclear methods
final class User extends AggregateRoot
{
    public function update(array $data): void { }    // ❌ Update what?
    public function process(): void { }              // ❌ Process what?
    public function handle(): void { }               // ❌ Handle what?
    public function execute(): void { }              // ❌ Execute what?
}
```

### Query Methods
```php
// ✅ SPRÁVNE - Descriptive queries
final class User extends AggregateRoot
{
    public function isActive(): bool { }
    public function isDeleted(): bool { }
    public function canChangeEmail(): bool { }
    public function canPerformActions(): bool { }
    public function hasPermission(Permission $permission): bool { }
}

final class Project extends AggregateRoot
{
    public function isOwnedBy(Uuid $userId): bool { }
    public function hasWorker(Uuid $userId): bool { }
    public function isArchived(): bool { }
    public function canAddWorker(): bool { }
}

// ❌ NESPRÁVNE - Unclear or generic
public function check(): bool { }           // ❌ Check what?
public function validate(): bool { }        // ❌ Validate what?
public function isValid(): bool { }         // ❌ Valid in what context?
```

### Event Handler Methods
```php
// ✅ SPRÁVNE - Event-specific naming
final class UserProjectCleanupHandler
{
    public function handleUserDeleted(UserDeletedEvent $event): void { }
    public function handleUserSuspended(UserSuspendedEvent $event): void { }
}

final class ProjectStatisticsUpdater
{
    public function handleProjectCreated(ProjectCreatedEvent $event): void { }
    public function handleWorkerAdded(ProjectWorkerAddedEvent $event): void { }
    public function handleWorkerRemoved(ProjectWorkerRemovedEvent $event): void { }
}

// ❌ NESPRÁVNE - Generic event handling
public function handle(DomainEvent $event): void { }  // ❌ Handle any event?
public function process($event): void { }             // ❌ No type, unclear
public function onEvent(object $event): void { }      // ❌ Generic handling
```

## Variable & Property Naming

### Descriptive Variables
```php
// ✅ SPRÁVNE - Self-documenting variables
public function deleteOrphanedProjects(Uuid $deletedUserId): void
{
    $orphanedProjects = $this->findProjectsByOwner($deletedUserId);
    $deletionResults = [];
    
    foreach ($orphanedProjects as $project) {
        $deletionResult = $this->deleteProject($project);
        $deletionResults[] = $deletionResult;
    }
    
    $this->logDeletionSummary($deletionResults);
}

// ❌ NESPRÁVNE - Unclear variables
public function deleteOrphanedProjects(Uuid $id): void
{
    $items = $this->find($id);          // ❌ items of what?
    $results = [];                      // ❌ results of what?
    
    foreach ($items as $item) {         // ❌ what is item?
        $result = $this->process($item); // ❌ process how?
        $results[] = $result;
    }
    
    $this->log($results);               // ❌ log what?
}
```

### Boolean Variables
```php
// ✅ SPRÁVNE - Clear boolean naming
$userIsActive = $user->isActive();
$projectHasWorkers = !empty($project->getWorkers());
$canPerformAction = $user->canPerformActions();
$shouldDeleteProject = $project->isOwnedBy($deletedUserId);

// ❌ NESPRÁVNE - Unclear boolean meaning
$status = $user->getStatus();     // ❌ Not boolean
$check = $this->validate($user);  // ❌ What does true/false mean?
$flag = true;                     // ❌ Flag for what?
```

## Event Naming

### Domain Events Pattern
**Events opisujú čo sa v minulosti stalo**

```php
// ✅ SPRÁVNE - Past tense, specific
final class UserRegisteredEvent implements DomainEvent { }
final class UserEmailChangedEvent implements DomainEvent { }
final class UserSuspendedEvent implements DomainEvent { }
final class UserDeletedEvent implements DomainEvent { }

final class ProjectCreatedEvent implements DomainEvent { }
final class ProjectRenamedEvent implements DomainEvent { }
final class ProjectWorkerAddedEvent implements DomainEvent { }
final class ProjectWorkerRemovedEvent implements DomainEvent { }
final class ProjectArchivedEvent implements DomainEvent { }

// ❌ NESPRÁVNE - Present tense or commands
final class UserRegisterEvent { }    // ❌ Present tense
final class CreateUserEvent { }      // ❌ Command, not event
final class UserEvent { }            // ❌ Too generic
final class UpdateEvent { }          // ❌ What was updated?
```

## Exception Naming

### Domain Exceptions
```php
// ✅ SPRÁVNE - Specific domain exceptions
final class UserNotFoundException extends UserDomainException { }
final class UserInactiveException extends UserDomainException { }
final class UserEmailAlreadyExistsException extends UserDomainException { }

final class ProjectNotFoundException extends ProjectDomainException { }
final class ProjectWorkerAlreadyExistsException extends ProjectDomainException { }
final class ProjectWorkerNotFoundException extends ProjectDomainException { }

// ❌ NESPRÁVNE - Generic or unclear
final class UserException { }        // ❌ What kind of user problem?
final class InvalidException { }     // ❌ Invalid what?
final class BusinessException { }    // ❌ Too generic
```

## Command & Query Naming

### Commands (Write Operations)
```php
// ✅ SPRÁVNE - Imperative, action-oriented
final class CreateUserCommand { }
final class DeleteUserCommand { }
final class ChangeUserEmailCommand { }
final class SuspendUserCommand { }

final class CreateProjectCommand { }
final class RenameProjectCommand { }
final class AddWorkerToProjectCommand { }
final class RemoveWorkerFromProjectCommand { }

// ❌ NESPRÁVNE - Not imperative
final class UserCommand { }          // ❌ Command for what?
final class UserCreation { }         // ❌ Not a command
final class UserData { }             // ❌ Data, not command
```

### Queries (Read Operations)
```php
// ✅ SPRÁVNE - Question-like naming
final class FindUserByIdQuery { }
final class FindUserByEmailQuery { }
final class FindActiveUsersQuery { }
final class GetUserStatisticsQuery { }

final class FindProjectsByOwnerQuery { }
final class FindProjectWithWorkersQuery { }
final class GetProjectStatisticsQuery { }

// ❌ NESPRÁVNE - Not query-like
final class UserQuery { }            // ❌ Query for what?
final class ProjectReader { }        // ❌ Technical terminology
final class DataRequest { }          // ❌ Too generic
```

## Repository Interface Naming

### Repository Patterns
```php
// ✅ SPRÁVNE - Domain-focused repository naming
interface UserRepositoryInterface
{
    public function save(User $user): void;
    public function findById(Uuid $id): ?User;
    public function findByEmail(Email $email): ?User;
    public function findActiveUsers(): array;
}

interface ProjectRepositoryInterface
{
    public function save(Project $project): void;
    public function findById(Uuid $id): ?Project;
    public function findByOwner(Uuid $ownerId): array;
    public function findWithWorkers(Uuid $projectId): ?Project;
}

// ❌ NESPRÁVNE - Technical or unclear
interface UserDataAccessInterface { }     // ❌ Technical focus
interface UserPersistence { }             // ❌ Implementation detail
interface UserStorage { }                 // ❌ Storage terminology
```

## Namespace Naming

### Consistent Structure
```php
// ✅ SPRÁVNE - Clear namespace hierarchy
namespace App\User\Domain\Model;
namespace App\User\Domain\Event;
namespace App\User\Domain\Exception;
namespace App\User\Domain\Repository;
namespace App\User\Domain\ValueObject;

namespace App\User\Application\Command;
namespace App\User\Application\Query;
namespace App\User\Application\EventHandler;

namespace App\User\Infrastructure\Persistence\Doctrine;
namespace App\User\Infrastructure\Http\Controller;

// ❌ NESPRÁVNE - Inconsistent or unclear
namespace App\UserStuff;              // ❌ Informal
namespace App\User\Helpers;           // ❌ Vague purpose
namespace App\User\Utils;             // ❌ Utility naming
namespace App\User\Misc;              // ❌ Miscellaneous
```

## Acronyms & Abbreviations

### When to Use Acronyms
```php
// ✅ SPRÁVNE - Well-known acronyms
final class UserDTO { }        // Data Transfer Object
final class ProjectAPI { }     // Application Programming Interface
final class UserUUID { }       // Universally Unique Identifier
final class JSONAPI { }        // JSON API

// ✅ SPRÁVNE - Prefer full names for clarity
final class HypertextTransferProtocolClient { }  // vs HTTPClient
final class StructuredQueryLanguageBuilder { }   // vs SQLBuilder

// ❌ NESPRÁVNE - Unclear abbreviations
final class UsrMgr { }         // ❌ User Manager?
final class PrjRepo { }        // ❌ Project Repository?
final class CmdHndlr { }       // ❌ Command Handler?
```

## File Naming

### File Name Conventions
```php
// ✅ SPRÁVNE - File names match class names
User.php                    // final class User
UserDeletedEvent.php        // final class UserDeletedEvent
DeleteUserHandler.php       // final class DeleteUserHandler
UserRepositoryInterface.php // interface UserRepositoryInterface

// ✅ SPRÁVNE - Test file naming
UserTest.php                       // Tests User class
DeleteUserHandlerTest.php          // Tests DeleteUserHandler
UserRepositoryIntegrationTest.php  // Integration test
```

## Naming Validation Checklist

Pri vytváraní každého názvu sa spýtaj:

- [ ] **Je názov samodokumentujúci?** Môžem pochopiť účel bez dodatočnej dokumentácie?
- [ ] **Používa doménový jazyk?** Rozumejú mu business experti?
- [ ] **Je špecifický pre kontext?** Nie je príliš všeobecný?
- [ ] **Dodržiava konvencie?** Konzistentný s ostatnými názvami?
- [ ] **Vyjadruje intent?** Jasne hovorí, čo robia metódy/triedy?
- [ ] **Je v správnom gramatickom čase?** Commands = imperative, Events = past tense
- [ ] **Neobsahuje zbytočné skratky?** Preferuje jasnosť pred stručnosťou

## Anti-Patterns to Avoid

### ❌ Common Naming Mistakes

1. **Manager/Helper/Util classes** - vague purpose
2. **Data/Info suffixes** - not descriptive
3. **Generic verbs** (process, handle, execute) - unclear action
4. **Technical terminology** in domain layer
5. **Inconsistent naming** across similar concepts
6. **Abbreviations** without clear meaning
7. **Present tense** for domain events
8. **Commands without imperative** form

### Refactoring Poor Names

```php
// ❌ BEFORE - Poor naming
final class UserManager
{
    public function process(array $data): void
    {
        $item = $this->validate($data);
        $this->handle($item);
    }
}

// ✅ AFTER - Clear naming
final class UserRegistrationService
{
    public function registerNewUser(array $registrationData): User
    {
        $validatedData = $this->validateRegistrationData($registrationData);
        return $this->createUserFromValidatedData($validatedData);
    }
}