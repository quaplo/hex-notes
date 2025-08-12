# Hex Notes - My Journey to DDD/CQRS/Event Sourcing 🗺️

This is documentation of my learning journey with **Domain-Driven Design**, **Hexagonal Architecture**, **CQRS**, and **Event Sourcing**. It's not a "best practice" guide, but rather a "work in progress" notebook full of experiments, attempts, and mistakes.

The code you'll find here is the result of my attempts to understand these complex concepts. Sometimes I manage to do something reasonable, other times... well, at least you can learn what not to do!

**Warning**: I treat this as my personal "coding dojo" - a place for practice and improvement. If you have better ideas (which is very likely), feel free to share!

## 🏗️ Architectural Patterns

### Domain-Driven Design (DDD)
- **Bounded Contexts**: Clearly separated domains (`Project`, `User`, `Shared`)
- **Aggregate Roots**: Consistent behavior and invariants
- **Value Objects**: Immutable objects with business logic
- **Domain Events**: Communication between aggregates
- **Repository Pattern**: Abstraction over persistence
- **Hybrid approach**: Different domains use different persistence strategies

### Hexagonal Architecture (Ports & Adapters)
```
┌─────────────────────────────────────────────────────────┐
│                    Infrastructure                        │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │
│  │   HTTP      │  │  Doctrine   │  │  Symfony    │     │
│  │ Controllers │  │ EventStore  │  │ Messenger   │     │
│  └─────────────┘  └─────────────┘  └─────────────┘     │
└─────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────┐
│                     Application                         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │
│  │  Commands   │  │   Queries   │  │   Handlers  │     │
│  │   & DTOs    │  │   & DTOs    │  │ & Services  │     │
│  └─────────────┘  └─────────────┘  └─────────────┘     │
└─────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────┐
│                       Domain                            │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │
│  │ Aggregates  │  │   Events    │  │ Value       │     │
│  │ & Entities  │  │ & Services  │  │ Objects     │     │
│  └─────────────┘  └─────────────┘  └─────────────┘     │
└─────────────────────────────────────────────────────────┘
```

### CQRS (Command Query Responsibility Segregation)
- **Command Bus**: Processing commands for state changes
- **Query Bus**: Optimized data reading
- **Separated models**: Write model (aggregates) vs Read model (projections)
- **Event Handlers**: Read model updates
- **Full encapsulation**: Commands & Queries use private constructor + factory methods
- **Immutable objects**: `readonly` properties with getter access

### Event Sourcing
- **Event Store**: Persistence of all domain events (Project domain)
- **Event Replay**: Aggregate reconstruction from events
- **Snapshots**: Optimization for large aggregates
- **Projection**: Building read models from events
- **Selective use**: Only for complex domains with rich history

## 🚀 Technologies

- **PHP 8.4+** - Latest PHP features and performance
- **Symfony 7.3** - Robust framework with DI container
- **Doctrine DBAL** - Database layer for Event Store
- **PostgreSQL 16** - Main database with JSON support
- **Symfony Messenger** - Hybrid processing (sync/async)
- **RabbitMQ 3.13** - Message broker for asynchronous events
- **Docker** - Containerization and easy deployment

## 🔄 Hybrid Event Processing

The project implements a sophisticated hybrid approach to event processing:

### Synchronous vs Asynchronous Events

**Synchronous Events (Domain Events)**
- Processed immediately within the same transaction
- Used for critical business operations
- Guaranteed consistency

**Asynchronous Events (Integration Events)**
- Processed via RabbitMQ message queue
- Used for cross-domain communication
- Eventual consistency

### Event Flow Architecture

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   User Domain   │    │   Integration    │    │ Project Domain  │
│                 │    │     Events       │    │                 │
│ ┌─────────────┐ │    │                  │    │ ┌─────────────┐ │
│ │ User.delete()│ │    │  ┌─────────────┐ │    │ │Delete       │ │
│ │             │ │    │  │ RabbitMQ    │ │    │ │Orphaned     │ │
│ │ ┌─────────┐ │ │    │  │ Queue       │ │    │ │Projects     │ │
│ │ │Domain   │ │ │───▶│  │             │ │───▶│ │             │ │
│ │ │Event    │ │ │    │  │async_events │ │    │ │ ┌─────────┐ │ │
│ │ └─────────┘ │ │    │  └─────────────┘ │    │ │ │Command  │ │ │
│ └─────────────┘ │    │                  │    │ │ │Handler  │ │ │
│                 │    │                  │    │ │ └─────────┘ │ │
└─────────────────┘    └──────────────────┘    │ └─────────────┘ │
                                               └─────────────────┘
```

### 🔧 Technical Event Implementation

**1. Domain Event Interface**
```php
// Base interface for all events
interface DomainEvent
{
    public function getOccurredAt(): DateTimeImmutable;
}
```

**2. Synchronous Domain Event (Event Sourcing)**
```php
// Project domain - event sourcing event
final readonly class ProjectCreatedEvent implements DomainEvent
{
    public function __construct(
        private Uuid $projectId,
        private ProjectName $projectName,
        private Uuid $ownerId,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable(),
    ) {}

    // Event Store serialization methods
    public function getEventName(): string
    {
        return 'project.created';
    }

    public function getEventData(): array
    {
        return [
            'projectId' => $this->projectId->toString(),
            'name' => $this->projectName->__toString(),
            'ownerId' => $this->ownerId->toString(),
            'occurredAt' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    public static function fromEventData(array $eventData): self
    {
        return new self(
            Uuid::create($eventData['projectId']),
            new ProjectName($eventData['name']),
            Uuid::create($eventData['ownerId']),
            new DateTimeImmutable($eventData['occurredAt'])
        );
    }
}
```

**3. Synchronous Domain Event (CRUD)**
```php
// User domain - simple domain event
final readonly class UserDeletedEvent implements DomainEvent
{
    public function __construct(
        private Uuid $uuid,
        private Email $email,
        private DateTimeImmutable $occurredAt,
    ) {}

    public static function create(Uuid $uuid, Email $email): self
    {
        return new self($uuid, $email, new DateTimeImmutable());
    }
}
```

**4. Asynchronous Integration Event**
```php
// Cross-domain integration event
final readonly class UserDeletedIntegrationEvent implements DomainEvent
{
    public function __construct(
        private Uuid $uuid,
        private string $userEmail,
        private DateTimeImmutable $occurredAt,
    ) {}

    // RabbitMQ serialization methods
    public function toArray(): array
    {
        return [
            'userId' => $this->uuid->toString(),
            'userEmail' => $this->userEmail,
            'occurredAt' => $this->occurredAt->format(DateTimeInterface::ATOM),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            Uuid::create($data['userId']),
            $data['userEmail'],
            new DateTimeImmutable($data['occurredAt'])
        );
    }
}
```

**5. Event Dispatching**

*Synchronous Domain Event Dispatcher*
```php
final readonly class DomainEventDispatcher implements EventDispatcher
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function dispatch(array $events): void
    {
        foreach ($events as $event) {
            // Synchronous processing via Symfony EventDispatcher
            $this->eventDispatcher->dispatch($event, $event::class);
        }
    }
}
```

*Domain Event → Integration Event Transformation*
```php
// User domain - synchronous domain event handler
class UserDeletedDomainEventHandler
{
    public function __construct(
        private MessageBusInterface $eventBus, // event.bus
    ) {}

    public function __invoke(UserDeletedEvent $domainEvent): void
    {
        // Transform Domain Event → Integration Event
        $integrationEvent = UserDeletedIntegrationEvent::create(
            $domainEvent->getUserId(),
            $domainEvent->getEmail()->__toString()
        );

        // Publish asynchronously via RabbitMQ
        $this->eventBus->dispatch($integrationEvent);
    }
}
```

*Asynchronous Integration Event Handler*
```php
// Project domain - asynchronous integration event handler
class UserDeletedIntegrationEventHandler
{
    public function __construct(
        private CommandBus $commandBus,
    ) {}

    public function __invoke(UserDeletedIntegrationEvent $event): void
    {
        // Processed asynchronously via RabbitMQ worker
        $this->commandBus->dispatch(
            DeleteOrphanedProjectsCommand::fromPrimitives(
                $event->getUserId()->toString()
            )
        );
    }
}
```

**6. Service Configuration**

*Synchronous Event Handlers*
```yaml
# config/services.yaml
services:
    # Domain event handler - synchronous
    App\User\Application\EventHandler\UserDeletedDomainEventHandler:
        arguments:
            $eventBus: '@event.bus'
        tags:
            - { name: kernel.event_listener, event: 'App\User\Domain\Event\UserDeletedEvent' }
    
    # Project event handlers - synchronous
    App\Project\Application\EventHandler\ProjectEventHandler:
        tags:
            - { name: kernel.event_listener, event: 'App\Project\Domain\Event\ProjectCreatedEvent' }
            - { name: kernel.event_listener, event: 'App\Project\Domain\Event\ProjectRenamedEvent' }
```

*Asynchronous Integration Event Handlers*
```yaml
# config/services.yaml
services:
    # Integration event handler - asynchronous
    App\Project\Application\EventHandler\UserDeletedIntegrationEventHandler:
        arguments:
            $commandBus: '@App\Shared\Application\CommandBus'
        tags:
            - { name: messenger.message_handler }  # Asynchronous via RabbitMQ
```

**7. Routing Configuration**

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            async_events: '%env(RABBITMQ_DSN)%'
        
        routing:
            # Integration events → RabbitMQ
            'App\Shared\Domain\Event\UserDeletedIntegrationEvent': async_events
```

## 📁 Project Structure

```
src/
├── Project/                    # Project Bounded Context (Event Sourcing)
│   ├── Domain/                 # Domain logic
│   │   ├── Model/             # Aggregates (Project.php) - Event Sourced
│   │   ├── Event/             # Domain events (5 event types)
│   │   ├── ValueObject/       # Value objects
│   │   └── Repository/        # Repository interface (Event Store)
│   ├── Application/           # Application layer
│   │   ├── Command/           # Command handlers
│   │   ├── Query/             # Query handlers (+ History queries)
│   │   └── EventHandler/      # Event handlers for projections
│   └── Infrastructure/        # Infrastructure layer
│       ├── Persistence/       # Event Store implementation
│       └── Projection/        # Read model projections
├── User/                      # User Bounded Context (CRUD)
│   ├── Domain/                # Domain logic
│   │   ├── Model/             # User aggregate - traditional model
│   │   ├── Event/             # Minimal events (only UserDeleted)
│   │   └── Repository/        # Repository interface (CRUD)
│   ├── Application/           # Application layer
│   │   ├── Command/           # CRUD operations
│   │   └── Query/             # Simple queries
│   └── Infrastructure/        # Infrastructure layer
│       └── Persistence/       # Doctrine ORM (UserEntity)
├── Shared/                    # Shared components
│   ├── Domain/                # Shared domain objects
│   ├── Application/           # Cross-domain communication
│   ├── Infrastructure/        # Shared infrastructure services
│   └── ValueObject/           # Shared value objects (Uuid, Email)
└── Infrastructure/            # Global infrastructure
    ├── Bus/                   # CQRS implementation
    ├── Http/                  # HTTP layer
    └── Persistence/           # Event Store + Doctrine
```

## 🏛️ Domains and Persistence Strategies

### Project Domain - Event Sourcing
```php
// Event Sourced aggregate
final class Project extends AggregateRoot
{
    // Reconstruction from events
    public function replayEvent(DomainEvent $event): void
    
    // Rich event history
    protected function handleEvent(DomainEvent $event): void {
        match ($event::class) {
            ProjectCreatedEvent::class => $this->handleProjectCreated($event),
            ProjectRenamedEvent::class => $this->handleProjectRenamed($event),
            ProjectDeletedEvent::class => $this->handleProjectDeleted($event),
            ProjectWorkerAddedEvent::class => $this->handleProjectWorkerAdded($event),
            ProjectWorkerRemovedEvent::class => $this->handleProjectWorkerRemoved($event),
        };
    }
}

// Event Store repository
interface ProjectRepositoryInterface
{
    public function save(Project $project): void;    // Saves events
    public function load(Uuid $uuid): ?Project;     // Reconstructs from events
}
```

### User Domain - Classic CRUD
```php
// Traditional aggregate with minimal events
final class User extends AggregateRoot
{
    // Direct state mutations
    public function changeEmail(Email $newEmail): void
    public function activate(): void
    public function deactivate(): void
    
    // Only critical events for cross-domain communication
    public function delete(): void {
        $this->apply(UserDeletedEvent::create($this->uuid, $this->email));
    }
}

// CRUD repository
interface UserRepositoryInterface
{
    public function save(User $user): void;              // Saves state
    public function findById(Uuid $uuid): ?User;        // Loads state
    public function findByEmail(Email $email): ?User;   // Query operations
}

// Doctrine Entity for persistence
#[ORM\Entity]
#[ORM\Table(name: 'users')]
class UserEntity
{
    // Database column mapping
    #[ORM\Column(type: 'uuid')] private string $id;
    #[ORM\Column(length: 255)] private string $email;
    #[ORM\Column(length: 20)] private string $status;
}
```

### When to Use Which Approach

**Event Sourcing (Project domain)**
- ✅ Complex business logic with rich history
- ✅ Need for auditability and replay functionality
- ✅ Frequent state changes requiring tracking
- ✅ Optimistic concurrency control
- ❌ Higher implementation complexity
- ❌ Need for snapshots for performance

**CRUD (User domain)**
- ✅ Simple CRUD operations
- ✅ Fast implementation and maintenance
- ✅ Straightforward queries and reports
- ✅ Lower infrastructure requirements
- ❌ Loss of change history
- ❌ Harder audit tracking

### Cross-Domain Communication
```php
// User domain publishes integration event
public function delete(): void {
    $this->apply(UserDeletedEvent::create($this->uuid, $this->email));
}

// Project domain reacts to integration event
class UserDeletedIntegrationEventHandler
{
    public function __invoke(UserDeletedIntegrationEvent $event): void
    {
        // Deletes all user's projects
        $this->deleteOrphanedProjectsHandler->__invoke(
            DeleteOrphanedProjectsCommand::create($event->getUserId())
        );
    }
}
```

## 🛠️ Development Tools

### Code Quality
- **PHPStan** - Static code analysis
- **PHP CS Fixer** - Automatic formatting
- **Rector** - Automatic refactoring
- **Deptrac** - Architectural dependency checks

### Testing
- **Pest PHP** - Modern testing framework
- **Feature tests** - End-to-end testing
- **Unit tests** - Business logic testing
- **Integration tests** - Component testing

### Architectural Controls
```yaml
# deptrac.yaml - Layer checks
Domain:
  - SharedDomain
  - SharedValueObject
  - SharedEvent

Application:
  - Domain
  - SharedDomain
  - SharedApplication
```

## 🚀 Quick Start

### Prerequisites
- Docker & Docker Compose
- Git

### Installation
```bash
# Clone repository
git clone <repository-url>
cd hex-notes

# Start with Docker (with RabbitMQ)
docker-compose up -d

# Install dependencies
docker exec hex-notes-app-1 composer install

# Run migrations for development database
docker exec hex-notes-app-1 php bin/console doctrine:migrations:migrate

# Run migrations for test database (required for integration tests)
docker exec hex-notes-app-1 php bin/console doctrine:migrations:migrate --env=test

# Application runs on http://localhost:8000
# RabbitMQ Management UI: http://localhost:15672 (admin/admin123)
```

> **Note**: The project uses a separate test database (`hex_notes_test`) for integration tests. Migrations must be run for both environments.

### Docker Services
```yaml
services:
  app:                    # PHP application
  db:                     # PostgreSQL database
  rabbitmq:              # RabbitMQ message broker
  messenger-worker:      # Asynchronous worker for RabbitMQ
```

### Development
```bash
# Run tests
composer test

# Code quality checks
composer phpstan
composer phpcs

# Architecture checks
composer deptrac

# Automatic fixes
composer phpcbf
composer rector
```

## 📚 Usage Examples

### Creating a New Project
```php
// Command with full encapsulation
$command = RegisterProjectCommand::fromPrimitives(
    'My New Project',
    '123e4567-e89b-12d3-a456-426614174000'
);

// Data access only through getters
$projectName = $command->getName();
$ownerId = $command->getOwnerId();

// Dispatch via Command Bus
$project = $commandBus->dispatch($command);
```

### Loading User's Projects
```php
// Query with private constructor
$query = FindProjectsByOwnerQuery::fromPrimitives(
    '123e4567-e89b-12d3-a456-426614174000'
);

// Immutable readonly properties
$ownerId = $query->getOwnerId();

// Dispatch via Query Bus
$projects = $queryBus->dispatch($query);
```

### CQRS Encapsulation Pattern
```php
final readonly class RenameProjectCommand
{
    // Private constructor - cannot be created directly
    private function __construct(
        private Uuid $projectId,
        private ProjectName $newName,
    ) {}

    // Factory method for safe creation
    public static function fromPrimitives(string $projectId, string $newName): self
    {
        return new self(
            Uuid::create($projectId),
            new ProjectName($newName)
        );
    }

    // Data access only through getters
    public function getProjectId(): Uuid
    {
        return $this->projectId;
    }

    public function getNewName(): ProjectName
    {
        return $this->newName;
    }
}
```

### Event Sourcing - Project History
```php
// Get complete history
$query = GetProjectHistoryQuery::fromPrimitives(
    'project-uuid'
);

$history = $queryBus->dispatch($query);
// Contains project + all events
```

## 🔧 Configuration

### Environment Variables
```bash
# .env
DATABASE_URL=postgresql://symfony:symfony@db:5432/hex_notes
RABBITMQ_DSN=amqp://admin:admin123@rabbitmq:5672/%2f/async_events
APP_ENV=dev
```

### Symfony Messenger
```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        default_bus: command.bus
        transports:
            async_events: '%env(RABBITMQ_DSN)%'
        buses:
            command.bus:
                middleware:
                    - doctrine_transaction
            query.bus:
                default_middleware: allow_no_handlers
            event.bus:
                default_middleware: allow_no_handlers
```

## 🧪 Testing

### Test Database
The project uses a separate test database for test isolation:
- **Development DB**: `hex_notes` (port 5432)
- **Test DB**: `hex_notes_test` (same container, different database)
- **Synchronous processing**: Tests use `sync://` transport for immediate processing

### Test Configuration
```bash
# .env.test
DATABASE_URL="postgresql://symfony:symfony@db:5432/hex_notes_test?serverVersion=16&charset=utf8"

# config/packages/test/messenger.yaml
transports:
    sync: 'sync://'  # Synchronous processing for tests
```

### Running Tests
```bash
# Prepare test database (only on first run)
docker exec hex-notes-app-1 php bin/console doctrine:migrations:migrate --env=test

# All tests
composer test

# With coverage
composer test-coverage

# Watch mode
composer test-watch

# Specific tests
vendor/bin/pest tests/Project/
vendor/bin/pest --filter="RegisterProjectHandler"
```

### RabbitMQ Worker Management
```bash
# Start worker manually
docker exec hex-notes-app-1 php bin/console messenger:consume async_events

# Monitor queue
docker exec hex-notes-app-1 php bin/console messenger:stats

# Stop all workers
docker exec hex-notes-app-1 php bin/console messenger:stop-workers

# RabbitMQ Management UI
# http://localhost:15672 (admin/admin123)
```

### Test Types

**Unit Tests**
- Test business logic in isolation
- Use in-memory implementations
- Fast and infrastructure-independent

**Integration Tests**
- Test component cooperation
- Use real database (test environment)
- Test Event Store, projections, handlers

**Feature Tests**
- End-to-end testing via HTTP API
- Test complete user stories
- Use Symfony test client

### Test Examples
```php
// Unit test
test('register project handler creates new project', function (): void {
    $repository = new InMemoryProjectRepository();
    $handler = new RegisterProjectHandler($repository);
    $command = ProjectTestFactory::createValidRegisterProjectCommand([
        'name' => 'Test Project',
    ]);

    $project = $handler($command);

    expect($project)->toBeInstanceOf(Project::class);
    expect((string) $project->getName())->toBe('Test Project');
});

// Feature test
it('can create user via HTTP API', function (): void {
    $client = static::createClient();
    
    $client->request('POST', '/api/users', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode(['email' => 'test@example.com']));

    expect($client->getResponse()->getStatusCode())->toBe(201);
});
```

## 🏛️ Architectural Principles

### Dependency Inversion
- Domain layer doesn't depend on infrastructure
- Interfaces in domain, implementations in infrastructure
- Dependency Injection via Symfony DI

### Single Responsibility
- Each class has one responsibility
- Command/Query handlers solve one operation
- Aggregates encapsulate related business logic

### Open/Closed Principle
- Extensibility through new handlers
- Adding new events without changing existing code
- Plugin architecture via Symfony events

## 🔄 Event-Driven Architecture

### Domain Events
```php
// Automatic publishing on aggregate change
$project = Project::create($name, $ownerId);
// Creates ProjectCreatedEvent

$project->rename($newName);
// Creates ProjectRenamedEvent
```

### Integration Events
```php
// Cross-context communication
$event = UserDeletedIntegrationEvent::create($userId, $email);
$eventBus->dispatch($event);
// Automatically deletes user's projects
```

### Event Handlers
```php
class ProjectEventHandler
{
    public function handleProjectCreated(ProjectCreatedEvent $event): void
    {
        // Update read model
        $this->projection->projectCreated($event);
    }
}
```

## 📈 Performance and Optimizations

### Event Store Optimizations
- Indexes on `aggregate_id`, `aggregate_type`
- JSON queries for PostgreSQL
- Snapshot strategy every 10 events

### Read Model Optimizations
- Denormalized tables for queries
- Optimized indexes
- Caching of frequent queries

### CQRS Benefits
- Independent read/write scaling
- Optimized query models
- Eventual consistency

### RabbitMQ Performance
- Persistent messages for durability
- Prefetch limit for worker load balancing
- Dead letter queues for failed messages
- Memory/time limits for workers

## 🚀 Production Deployment

### Docker Production
```dockerfile
# Multi-stage build for production
FROM php:8.4-fpm-alpine
# AMQP extension for RabbitMQ
RUN pecl install amqp && docker-php-ext-enable amqp
```

### Monitoring
- Symfony Profiler for development
- RabbitMQ Management UI for queue monitoring
- Doctrine query logging
- Event store metrics
- Messenger worker health checks

### Scaling
- Horizontal scaling of read models
- Multiple RabbitMQ workers
- Asynchronous event processing
- Database sharding possibilities
- RabbitMQ clustering for HA

## 🤝 Contributing

### Coding Standards
```bash
# Before commit
composer phpcs
composer phpstan
composer deptrac
composer test
```

### Architectural Rules
1. Domain layer must not depend on infrastructure
2. Each command/query has its own handler
3. Aggregates communicate only through events
4. All changes must be covered by tests

### Adding New Functionality
1. Create command/query in Application layer
2. Implement handler with business logic
3. Add tests (unit + integration)
4. Update documentation

## 📖 Further Reading

- [Domain-Driven Design](https://martinfowler.com/bliki/DomainDrivenDesign.html)
- [CQRS Pattern](https://martinfowler.com/bliki/CQRS.html)
- [Event Sourcing](https://martinfowler.com/eaaDev/EventSourcing.html)
- [Hexagonal Architecture](https://alistair.cockburn.us/hexagonal-architecture/)

## 📄 License

This project is licensed under [MIT License](LICENSE).

---

**Hex Notes** - Modern template for enterprise PHP applications with DDD/CQRS/Event Sourcing 🚀