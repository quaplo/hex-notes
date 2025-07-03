# CQRS (Command Query Responsibility Segregation)

## CQRS Fundamentals

Tento projekt implementuje CQRS pattern s jasným rozdelením medzi Commands (write) a Queries (read). Ako AI asistent musíš dodržiavať túto separáciu.

## Command Side (Write Model)

### Command Design
**Commands reprezentujú intent používateľa**

```php
// ✅ SPRÁVNE - Command as DTO
final class DeleteUserCommand
{
    public function __construct(
        private readonly Uuid $userId
    ) {}

    public function getUserId(): Uuid
    {
        return $this->userId;
    }
}

// ✅ SPRÁVNE - Rich command s validáciou
final class CreateProjectCommand
{
    public function __construct(
        private readonly string $name,
        private readonly Uuid $ownerId
    ) {
        if (empty($name)) {
            throw new InvalidArgumentException('Project name cannot be empty');
        }
    }

    public function getName(): string { return $this->name; }
    public function getOwnerId(): Uuid { return $this->ownerId; }
}
```

### Command Handlers
**Jeden handler na jeden command**

```php
// ✅ SPRÁVNE - Command handler pattern
final class DeleteUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private DomainEventDispatcherInterface $eventDispatcher
    ) {}

    public function __invoke(DeleteUserCommand $command): void
    {
        // 1. Load aggregate
        $user = $this->userRepository->findById($command->getUserId());
        if (!$user) {
            throw new UserNotFoundException($command->getUserId());
        }

        // 2. Execute business logic
        $user->delete();

        // 3. Persist changes
        $this->userRepository->save($user);

        // 4. Publish events
        $this->eventDispatcher->dispatch($user->getUncommittedEvents());
    }
}
```

### Command Handler Pattern
**Štandardný flow pre všetky command handlers**

```php
// Template pattern pre command handlers
abstract class CommandHandler
{
    final public function handle(object $command): void
    {
        $this->validate($command);           // 1. Validation
        $aggregate = $this->loadAggregate($command);  // 2. Load
        $this->executeBusinessLogic($aggregate, $command); // 3. Business logic
        $this->persistChanges($aggregate);   // 4. Persist
        $this->publishEvents($aggregate);    // 5. Events
    }

    abstract protected function validate(object $command): void;
    abstract protected function loadAggregate(object $command): AggregateRoot;
    abstract protected function executeBusinessLogic(AggregateRoot $aggregate, object $command): void;
    abstract protected function persistChanges(AggregateRoot $aggregate): void;
    abstract protected function publishEvents(AggregateRoot $aggregate): void;
}
```

## Query Side (Read Model)

### Query Design
**Queries reprezentujú informačnú potrebu**

```php
// ✅ SPRÁVNE - Query as DTO
final class FindProjectsByOwnerQuery
{
    public function __construct(
        private readonly Uuid $ownerId,
        private readonly ?int $limit = null,
        private readonly ?int $offset = null
    ) {}

    public function getOwnerId(): Uuid { return $this->ownerId; }
    public function getLimit(): ?int { return $this->limit; }
    public function getOffset(): ?int { return $this->offset; }
}

// ✅ SPRÁVNE - Complex query with filters
final class FindUsersQuery
{
    public function __construct(
        private readonly ?UserStatus $status = null,
        private readonly ?string $emailFilter = null,
        private readonly ?DateTimeImmutable $createdAfter = null,
        private readonly int $limit = 50,
        private readonly int $offset = 0
    ) {}
}
```

### Query Handlers
**Optimalizované pre reading**

```php
// ✅ SPRÁVNE - Query handler returns DTOs
final class FindProjectsByOwnerHandler
{
    public function __construct(
        private ProjectReadModelRepositoryInterface $readRepository
    ) {}

    /**
     * @return ProjectListItemDto[]
     */
    public function __invoke(FindProjectsByOwnerQuery $query): array
    {
        return $this->readRepository->findByOwner(
            $query->getOwnerId(),
            $query->getLimit(),
            $query->getOffset()
        );
    }
}

// Read model repository - optimized for queries
interface ProjectReadModelRepositoryInterface
{
    /** @return ProjectListItemDto[] */
    public function findByOwner(Uuid $ownerId, ?int $limit, ?int $offset): array;
    
    /** @return ProjectDetailDto */
    public function findDetailById(Uuid $projectId): ?ProjectDetailDto;
    
    /** @return ProjectStatsDto[] */
    public function getStatsByUser(Uuid $userId): array;
}
```

### Read Model DTOs
**Optimalizované data transfer objects**

```php
// ✅ SPRÁVNE - Flat DTO pre UI potreby
final class ProjectListItemDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $ownerEmail,
        public readonly DateTimeImmutable $createdAt,
        public readonly int $workerCount,
        public readonly bool $isDeleted
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['name'],
            $data['owner_email'],
            new DateTimeImmutable($data['created_at']),
            (int) $data['worker_count'],
            (bool) $data['is_deleted']
        );
    }
}

// ✅ SPRÁVNE - Detailed DTO pre detail view
final class ProjectDetailDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly UserDto $owner,
        public readonly array $workers, // ProjectWorkerDto[]
        public readonly DateTimeImmutable $createdAt,
        public readonly ?DateTimeImmutable $deletedAt
    ) {}
}
```

## CQRS Architectural Rules

### Separation Concerns

#### ✅ SPRÁVNE - Clear separation
```php
// Write side - works with aggregates
final class CreateProjectHandler
{
    public function __invoke(CreateProjectCommand $command): void
    {
        $project = Project::create(
            new ProjectName($command->getName()),
            $command->getOwnerId()
        );
        $this->projectRepository->save($project); // Aggregate repository
    }
}

// Read side - works with DTOs
final class FindProjectHandler  
{
    public function __invoke(FindProjectQuery $query): ?ProjectDetailDto
    {
        return $this->readRepository->findDetailById($query->getId()); // Read model
    }
}
```

#### ❌ NESPRÁVNE - Mixed concerns
```php
// ❌ Query handler nesmie modifikovať stav
final class FindProjectHandler
{
    public function __invoke(FindProjectQuery $query): ?ProjectDetailDto
    {
        $project = $this->projectRepository->findById($query->getId());
        $project->incrementViewCount(); // ❌ Modification in query!
        return ProjectDetailDto::from($project);
    }
}
```

### Repository Separation

#### Write Model Repository
```php
// ✅ SPRÁVNE - Write repository works with aggregates
interface UserRepositoryInterface
{
    public function save(User $user): void;
    public function findById(Uuid $id): ?User;
    public function findByEmail(Email $email): ?User;
}

// Implementation focuses on aggregate loading/saving
final class DoctrineUserRepository implements UserRepositoryInterface
{
    public function save(User $user): void
    {
        $entity = $this->mapper->toDoctrineEntity($user);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
}
```

#### Read Model Repository
```php
// ✅ SPRÁVNE - Read repository optimized for queries
interface UserReadModelRepositoryInterface
{
    /** @return UserListItemDto[] */
    public function findAll(int $limit, int $offset): array;
    
    /** @return UserListItemDto[] */
    public function findByStatus(UserStatus $status): array;
    
    public function getUserStats(): UserStatsDto;
}

// Implementation uses optimized queries, denormalized data
final class DoctrineUserReadRepository implements UserReadModelRepositoryInterface
{
    public function findAll(int $limit, int $offset): array
    {
        // Optimized SQL query, joins, projections
        $qb = $this->entityManager->createQueryBuilder()
            ->select('u.id, u.email, u.status, u.created_at')
            ->from(UserProjection::class, 'u')
            ->setMaxResults($limit)
            ->setFirstResult($offset);
            
        return array_map(
            fn($row) => UserListItemDto::fromArray($row),
            $qb->getQuery()->getArrayResult()
        );
    }
}
```

## Event-Driven Read Model Updates

### Projection Updates
**Read models sa aktualizujú cez domain events**

```php
// ✅ SPRÁVNE - Event handler updates read model
final class UserProjectionUpdater
{
    public function __construct(
        private UserProjectionRepositoryInterface $projectionRepository
    ) {}

    public function handleUserDeleted(UserDeletedEvent $event): void
    {
        // Update read model/projection
        $this->projectionRepository->markAsDeleted(
            $event->getUserId(),
            $event->getOccurredAt()
        );
    }

    public function handleUserCreated(UserCreatedEvent $event): void
    {
        $projection = new UserProjection(
            $event->getUserId(),
            $event->getEmail(),
            $event->getStatus(),
            $event->getOccurredAt()
        );
        
        $this->projectionRepository->save($projection);
    }
}
```

### Eventually Consistent Reads
**Read models môžu byť eventually consistent**

```php
// ✅ SPRÁVNE - Accept eventual consistency
final class ProjectStatsHandler
{
    public function __invoke(GetProjectStatsQuery $query): ProjectStatsDto
    {
        // Read model môže byť o pár milisekúnd pozadu
        $stats = $this->readRepository->getStats($query->getProjectId());
        
        return new ProjectStatsDto(
            $stats->projectId,
            $stats->workerCount,
            $stats->tasksCompleted,
            $stats->lastUpdated // Show when data was last updated
        );
    }
}
```

## Bus Architecture

### Command Bus
**Centrálne spracovanie commands**

```php
// Command bus interface
interface CommandBusInterface
{
    public function dispatch(object $command): void;
}

// Usage in controller
final class ProjectController
{
    public function __construct(private CommandBusInterface $commandBus) {}

    public function create(Request $request): Response
    {
        $command = new CreateProjectCommand(
            $request->get('name'),
            Uuid::fromString($request->get('owner_id'))
        );
        
        $this->commandBus->dispatch($command);
        
        return new Response('', 201);
    }
}
```

### Query Bus
**Centrálne spracovanie queries**

```php
// Query bus interface
interface QueryBusInterface
{
    public function ask(object $query): mixed;
}

// Usage in controller
final class ProjectController
{
    public function __construct(private QueryBusInterface $queryBus) {}

    public function list(Request $request): Response
    {
        $query = new FindProjectsByOwnerQuery(
            Uuid::fromString($request->get('owner_id')),
            (int) $request->get('limit', 20),
            (int) $request->get('offset', 0)
        );
        
        $projects = $this->queryBus->ask($query);
        
        return $this->json($projects);
    }
}
```

## CQRS Best Practices

### DO's ✅

1. **Single Responsibility** - jeden handler pre jeden command/query
2. **Immutable Commands/Queries** - DTOs sú read-only
3. **Separate Repositories** - write vs read repositories
4. **Event-Driven Projections** - update read models cez events
5. **Return DTOs from Queries** - nie domain objekty
6. **Validate Commands** - business rules validation
7. **Idempotent Commands** - safe to retry

### DON'Ts ❌

1. **Nemodifikuj v query handlers** - iba čítanie
2. **Nevracaj aggregates z queries** - iba DTOs
3. **Nemiešaj read/write repositories** - jasná separácia
4. **Nepoužívaj domain objekty v read models** - optimalizované DTOs
5. **Nezabúdaj na eventual consistency** - read models môžu zaostávať

## Testing CQRS

### Command Testing
```php
test('delete user command removes user')
    ->expect(function() {
        $user = User::register(Email::fromString('test@example.com'));
        $this->userRepository->save($user);
        
        $command = new DeleteUserCommand($user->getId());
        $this->commandHandler->__invoke($command);
        
        return $this->userRepository->findById($user->getId());
    })
    ->toBeNull();
```

### Query Testing
```php
test('find projects by owner returns correct data')
    ->expect(function() {
        $query = new FindProjectsByOwnerQuery($ownerId, 10, 0);
        return $this->queryHandler->__invoke($query);
    })
    ->toBeArray()
    ->toHaveCount(2)
    ->and(fn($projects) => $projects[0])
    ->toBeInstanceOf(ProjectListItemDto::class);