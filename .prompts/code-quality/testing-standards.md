# Testing Standards & TDD Practices

## Testing Philosophy

Tento projekt používa Test-Driven Development (TDD) s dôrazom na testovateľnosť DDD architektúry. Ako AI asistent musíš zabezpečiť vysokú kvalitu testov.

## Test Strategy

### Test Pyramid
```
     /\
    /  \    E2E Tests (Integration)
   /____\   
  /      \   Unit Tests (Domain + Application)
 /        \  
/__________\  Unit Tests (Fast, Isolated)
```

### Testing Layers

#### 1. Domain Layer Tests (Unit)
**Fastest, most isolated tests**

```php
// ✅ SPRÁVNE - Pure domain logic testing
final class UserTest extends TestCase
{
    public function test_user_can_be_registered(): void
    {
        // Arrange
        $email = Email::fromString('test@example.com');
        
        // Act
        $user = User::register($email);
        
        // Assert
        $this->assertTrue($user->isActive());
        $this->assertEquals($email, $user->getEmail());
        $this->assertInstanceOf(Uuid::class, $user->getId());
    }

    public function test_user_deletion_records_domain_event(): void
    {
        // Arrange
        $user = User::register(Email::fromString('test@example.com'));
        
        // Act
        $user->delete();
        
        // Assert
        $events = $user->getUncommittedEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserDeletedEvent::class, $events[0]);
    }

    public function test_inactive_user_cannot_change_email(): void
    {
        // Arrange
        $user = User::register(Email::fromString('test@example.com'));
        $user->deactivate();
        
        // Act & Assert
        $this->expectException(UserInactiveException::class);
        $user->changeEmail(Email::fromString('new@example.com'));
    }
}
```

#### 2. Application Layer Tests (Unit)
**Test orchestration and coordination**

```php
// ✅ SPRÁVNE - Command handler testing with mocks
final class DeleteUserHandlerTest extends TestCase
{
    private UserRepositoryInterface $userRepository;
    private DomainEventDispatcherInterface $eventDispatcher;
    private DeleteUserHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $this->handler = new DeleteUserHandler(
            $this->userRepository,
            $this->eventDispatcher
        );
    }

    public function test_deletes_existing_user(): void
    {
        // Arrange
        $userId = Uuid::generate();
        $user = User::register(Email::fromString('test@example.com'));
        
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);
            
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);
            
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (array $events) {
                return count($events) === 1 && 
                       $events[0] instanceof UserDeletedEvent;
            }));

        // Act
        $this->handler->__invoke(new DeleteUserCommand($userId));

        // Assert
        $this->assertTrue($user->isDeleted());
    }

    public function test_throws_exception_when_user_not_found(): void
    {
        // Arrange
        $userId = Uuid::generate();
        
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn(null);

        // Act & Assert
        $this->expectException(UserNotFoundException::class);
        $this->handler->__invoke(new DeleteUserCommand($userId));
    }
}
```

#### 3. Integration Tests
**Test real infrastructure interactions**

```php
// ✅ SPRÁVNE - Integration test s reálnou infraštruktúrou
final class UserRepositoryIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->app->make(UserRepository::class);
    }

    public function test_can_save_and_retrieve_user(): void
    {
        // Arrange
        $user = User::register(Email::fromString('test@example.com'));
        
        // Act
        $this->repository->save($user);
        $retrievedUser = $this->repository->findById($user->getId());
        
        // Assert
        $this->assertNotNull($retrievedUser);
        $this->assertEquals($user->getId(), $retrievedUser->getId());
        $this->assertEquals($user->getEmail(), $retrievedUser->getEmail());
    }

    public function test_deleted_user_is_marked_as_deleted(): void
    {
        // Arrange
        $user = User::register(Email::fromString('test@example.com'));
        $this->repository->save($user);
        
        // Act
        $user->delete();
        $this->repository->save($user);
        
        // Assert
        $retrievedUser = $this->repository->findById($user->getId());
        $this->assertTrue($retrievedUser->isDeleted());
        $this->assertNotNull($retrievedUser->getDeletedAt());
    }
}
```

## TDD Workflow

### Red-Green-Refactor Cycle

#### 1. Red - Write Failing Test
```php
// ✅ KROK 1: Napíš failing test
public function test_project_can_be_renamed(): void
{
    // Arrange
    $project = Project::create(
        new ProjectName('Original Name'),
        Uuid::generate()
    );
    
    $newName = new ProjectName('New Name');
    
    // Act
    $renamedProject = $project->rename($newName);
    
    // Assert
    $this->assertEquals($newName, $renamedProject->getName());
    $this->assertInstanceOf(ProjectRenamedEvent::class, 
        $renamedProject->getUncommittedEvents()[0]);
}
```

#### 2. Green - Make Test Pass
```php
// ✅ KROK 2: Implementuj minimum code to pass
final class Project extends AggregateRoot
{
    public function rename(ProjectName $newName): self
    {
        if ($this->isDeleted()) {
            throw new \DomainException('Cannot rename deleted project');
        }

        $oldName = $this->name;
        $project = new self(
            $this->id,
            $newName,  // ✅ Simple implementation
            $this->createdAt,
            $this->ownerId,
            $this->deletedAt
        );

        $project->workers = $this->workers;
        $project->setVersion($this->getVersion());
        $project->recordEvent(new ProjectRenamedEvent(
            $project->getId(), 
            $oldName, 
            $newName
        ));

        return $project;
    }
}
```

#### 3. Refactor - Improve Code Quality
```php
// ✅ KROK 3: Refactor for better design
final class Project extends AggregateRoot
{
    public function rename(ProjectName $newName): self
    {
        $this->ensureNotDeleted(); // ✅ Extracted method
        
        if ($this->name->equals($newName)) {
            return $this; // ✅ No change optimization
        }

        return $this->createModifiedCopy(
            name: $newName,
            events: [new ProjectRenamedEvent($this->id, $this->name, $newName)]
        );
    }

    private function ensureNotDeleted(): void
    {
        if ($this->isDeleted()) {
            throw new \DomainException('Cannot modify deleted project');
        }
    }

    private function createModifiedCopy(
        ?ProjectName $name = null,
        array $events = []
    ): self {
        $project = new self(
            $this->id,
            $name ?? $this->name,
            $this->createdAt,
            $this->ownerId,
            $this->deletedAt
        );

        $project->workers = $this->workers;
        $project->setVersion($this->getVersion());
        
        foreach ($events as $event) {
            $project->recordEvent($event);
        }

        return $project;
    }
}
```

## Testing Patterns

### 1. Arrange-Act-Assert (AAA)
**Štandardný pattern pre všetky testy**

```php
public function test_user_registration_creates_active_user(): void
{
    // Arrange - Setup test data
    $email = Email::fromString('test@example.com');
    
    // Act - Execute the behavior
    $user = User::register($email);
    
    // Assert - Verify the outcome
    $this->assertTrue($user->isActive());
    $this->assertEquals($email, $user->getEmail());
}
```

### 2. Given-When-Then (BDD Style)
**Pre complex business scenarios**

```php
public function test_project_worker_removal_scenario(): void
{
    // Given - Initial state
    $project = Project::create(new ProjectName('Test'), Uuid::generate());
    $worker = ProjectWorker::create(Uuid::generate(), WorkerRole::DEVELOPER);
    $project = $project->addWorker($worker);
    
    // When - Action occurs
    $project = $project->removeWorkerByUserId($worker->getUserId());
    
    // Then - Expected outcome
    $this->assertEmpty($project->getWorkers());
    $this->assertInstanceOf(
        ProjectWorkerRemovedEvent::class,
        $project->getUncommittedEvents()[1] // Second event after worker added
    );
}
```

### 3. Object Mother Pattern
**Pre complex test data creation**

```php
// ✅ SPRÁVNE - Object Mother for test data
final class UserMother
{
    public static function active(): User
    {
        return User::register(Email::fromString('active@example.com'));
    }

    public static function deleted(): User
    {
        $user = self::active();
        $user->delete();
        return $user;
    }

    public static function suspended(): User
    {
        $user = self::active();
        $user->suspend();
        return $user;
    }

    public static function withEmail(string $email): User
    {
        return User::register(Email::fromString($email));
    }
}

// Usage in tests
public function test_suspended_user_cannot_perform_actions(): void
{
    // Arrange
    $user = UserMother::suspended(); // ✅ Clear and concise
    
    // Act & Assert
    $this->expectException(UserInactiveException::class);
    $user->changeEmail(Email::fromString('new@example.com'));
}
```

## Event Sourcing Testing

### 1. Event-Based Assertions
**Test behavior through events**

```php
public function test_user_deletion_publishes_correct_event(): void
{
    // Arrange
    $user = User::register(Email::fromString('test@example.com'));
    
    // Act
    $user->delete();
    
    // Assert - Check events instead of state
    $events = $user->getUncommittedEvents();
    $this->assertCount(1, $events);
    
    $event = $events[0];
    $this->assertInstanceOf(UserDeletedEvent::class, $event);
    $this->assertEquals($user->getId(), $event->getUserId());
    $this->assertEquals($user->getEmail(), $event->getEmail());
}
```

### 2. Event Replay Testing
**Test aggregate reconstruction**

```php
public function test_project_can_be_reconstructed_from_events(): void
{
    // Arrange
    $projectId = Uuid::generate();
    $ownerId = Uuid::generate();
    $originalName = new ProjectName('Original');
    $newName = new ProjectName('Renamed');
    
    $events = [
        new ProjectCreatedEvent($projectId, $originalName, $ownerId),
        new ProjectRenamedEvent($projectId, $originalName, $newName)
    ];
    
    // Act - Reconstruct from events
    $project = Project::createEmpty();
    foreach ($events as $event) {
        $project->replayEvent($event);
    }
    
    // Assert
    $this->assertEquals($projectId, $project->getId());
    $this->assertEquals($newName, $project->getName());
    $this->assertEquals($ownerId, $project->getOwnerId());
    $this->assertEquals(2, $project->getVersion());
}
```

## Test Doubles

### 1. Mocks for Behavior Verification
```php
public function test_command_handler_calls_repository(): void
{
    // Arrange
    $repository = $this->createMock(UserRepositoryInterface::class);
    $repository
        ->expects($this->once())
        ->method('save')
        ->with($this->isInstanceOf(User::class));
    
    $handler = new CreateUserHandler($repository);
    
    // Act
    $handler(new CreateUserCommand('test@example.com'));
    
    // Assert is handled by mock expectations
}
```

### 2. Stubs for State Setup
```php
public function test_handler_with_existing_user(): void
{
    // Arrange
    $user = User::register(Email::fromString('test@example.com'));
    
    $repository = $this->createStub(UserRepositoryInterface::class);
    $repository
        ->method('findByEmail')
        ->willReturn($user);
    
    $handler = new FindUserByEmailHandler($repository);
    
    // Act
    $result = $handler(new FindUserByEmailQuery('test@example.com'));
    
    // Assert
    $this->assertEquals($user, $result);
}
```

### 3. Test Doubles for Event Dispatching
```php
public function test_events_are_dispatched(): void
{
    // Arrange
    $eventDispatcher = new TestEventDispatcher(); // Custom test double
    $handler = new DeleteUserHandler($this->userRepository, $eventDispatcher);
    
    // Act
    $handler(new DeleteUserCommand($userId));
    
    // Assert
    $this->assertCount(1, $eventDispatcher->getDispatchedEvents());
    $this->assertInstanceOf(
        UserDeletedEvent::class, 
        $eventDispatcher->getDispatchedEvents()[0]
    );
}
```

## Testing Best Practices

### DO's ✅

1. **Test Behavior, Not Implementation** - focus on what, not how
2. **One Assertion Per Test** - clear test failure messages
3. **Descriptive Test Names** - describe the scenario
4. **Fast Tests** - avoid I/O in unit tests
5. **Independent Tests** - no test dependencies
6. **Use Object Mothers** - for complex test data
7. **Test Edge Cases** - boundary conditions
8. **Mock External Dependencies** - control test environment

### DON'Ts ❌

1. **Don't Test Private Methods** - test public behavior
2. **Don't Mock Value Objects** - use real instances
3. **Don't Test Framework Code** - focus on your logic
4. **Don't Write Slow Unit Tests** - use integration tests for I/O
5. **Don't Ignore Failing Tests** - fix or delete
6. **Don't Over-Mock** - real objects when simple
7. **Don't Test Getters/Setters** - unless they have logic

## Test Organization

### File Structure
```
tests/
├── Unit/
│   ├── User/
│   │   ├── Domain/
│   │   │   ├── Model/
│   │   │   │   └── UserTest.php
│   │   │   └── ValueObject/
│   │   │       └── EmailTest.php
│   │   └── Application/
│   │       └── Command/
│   │           └── DeleteUserHandlerTest.php
│   └── Project/
├── Integration/
│   ├── UserRepositoryTest.php
│   └── ProjectRepositoryTest.php
├── Feature/
│   ├── UserManagementTest.php
│   └── ProjectManagementTest.php
└── Helpers/
    ├── UserMother.php
    └── ProjectMother.php
```

### Test Configuration
```php
// ✅ SPRÁVNE - Base test class
abstract class UnitTestCase extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close(); // Clean up mocks
        parent::tearDown();
    }
}

abstract class IntegrationTestCase extends TestCase
{
    use DatabaseTransactions; // Rollback after each test
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }
}
```

## Test Metrics

### Coverage Goals
- **Domain Layer**: 100% line coverage
- **Application Layer**: 95% line coverage
- **Infrastructure Layer**: 80% line coverage (focus on adapters)

### Quality Metrics
```bash
# Run tests with coverage
vendor/bin/pest --coverage

# Check specific coverage
vendor/bin/pest --coverage --min=95

# Test specific domain
vendor/bin/pest tests/Unit/User/Domain/

# Watch mode for TDD
vendor/bin/pest --watch
```

## Architecture Testing

### Dependency Rules Testing
```php
test('domain layer has no external dependencies')
    ->expect('App\User\Domain')
    ->not->toUse(['Doctrine', 'Symfony', 'Laravel']);

test('application layer depends only on domain')
    ->expect('App\User\Application')
    ->toOnlyUse([
        'App\User\Domain',
        'App\Shared\Domain',
        'DateTimeImmutable'
    ]);
```

### Event Sourcing Rules Testing
```php
test('all aggregates implement event handling')
    ->expect('App\User\Domain\Model\User')
    ->toExtend('App\Shared\Domain\Model\AggregateRoot')
    ->and('App\Project\Domain\Model\Project')
    ->toExtend('App\Shared\Domain\Model\AggregateRoot');

test('all domain events are immutable')
    ->expect('App\User\Domain\Event')
    ->classes()
    ->toBeFinal()
    ->toHaveMethod('getOccurredAt');