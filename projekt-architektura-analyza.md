# Analýza architektúry projektu - DDD a Hexagonálna architektúra

## 🚨 Kritické problémy

### 1. Porušenie Dependency Inversion Principle (DIP)

**Problém**: Application layer priamo závisí od Infrastructure layer
```php
// ❌ Zlé - ProjectService.php
public function __construct(
    private ProjectEventStoreRepository $projectRepository // Infrastructure dependency!
) {}
```

**Riešenie**: Vytvoriť domain repository interface
```php
// ✅ Dobré - vytvoriť src/Project/Domain/Repository/ProjectRepositoryInterface.php
interface ProjectRepositoryInterface {
    public function save(Project $project): void;
    public function load(Uuid $id): ?Project;
    public function findAll(): array;
}
```

### 2. Nesprávne umiestnenie business logiky

**Problém**: `ProjectService` obsahuje domain logic
```php
// ❌ Zlé - business rules v application service
public function renameProject(string $projectId, string $newName): Project {
    $project = $this->projectRepository->load(new Uuid($projectId));
    if (!$project) {
        throw new \DomainException("Project with id $projectId not found");
    }
    // ...
}
```

**Riešenie**: Presunúť do Command Handlers
```php
// ✅ Dobré - RenameProjectHandler.php
public function __invoke(RenameProjectCommand $command): void {
    $project = $this->projectRepository->load($command->projectId);
    if (!$project) {
        throw new ProjectNotFoundException($command->projectId);
    }
    
    $renamedProject = $project->rename($command->newName);
    $this->projectRepository->save($renamedProject);
}
```

### 3. ✅ VYRIEŠENÉ: Duplicitné event handling mechanizmy

**Problém**: `ProjectDomainEventsTrait` duplikoval funkcionalitu `AggregateRoot`
```php
// ❌ Zlé - ProjectDomainEventsTrait mal vlastné handleEvent
protected function handleEvent(DomainEvent $event): void {
    match (get_class($event)) {
        // ...
    };
}

// AggregateRoot už má apply/replayEvent mechanizmus
```

**✅ IMPLEMENTOVANÉ RIEŠENIE**:
- Odstránený `ProjectDomainEventsTrait`
- `Project` trieda teraz konzistentne používa `AggregateRoot` mechanizmus
- Všetky event recording operácie používajú `apply()` metódu
- Implementovaná `handleEvent()` metóda priamo v `Project` triede

```php
// ✅ Vyriešené - Project.php používa apply() z AggregateRoot
public function addWorker(ProjectWorker $worker): self {
    // validation...
    $project->apply(new ProjectWorkerAddedEvent(/*...*/));
    return $project;
}

// Implementované handleEvent v Project triede
protected function handleEvent(DomainEvent $event): void {
    match (get_class($event)) {
        ProjectCreatedEvent::class => $this->handleProjectCreated($event),
        ProjectRenamedEvent::class => $this->handleProjectRenamed($event),
        // ...
    };
}
```

## 📋 Stredne kritické problémy

### 4. Nekonzistentné Command Handler implementácie

**Problém**: Niektoré handlery používajú `ProjectService`, iné by mali byť priame
```php
// ❌ Nekonzistentné
class AddProjectWorkerHandler {
    public function __construct(private ProjectService $projectService) {}
}
```

**Riešenie**: Priama závislosť na repository
```php
// ✅ Dobré
class AddProjectWorkerHandler {
    public function __construct(private ProjectRepositoryInterface $projectRepository) {}
}
```

### 5. ProjectWorker ako Value Object

**Problém**: Má mutable operácie (`withRole`) ale modelovaný ako VO
```php
// ❌ Zmätočné - value object s mutation
public function withRole(ProjectRole $role): self {
    return new self(/* ... */);
}
```

**Riešenie**: Buď Entity alebo čisto immutable VO
```php
// ✅ Option A: Entity s ID
class ProjectWorker {
    private ProjectWorkerId $id;
    public function changeRole(ProjectRole $role): void { /* ... */ }
}

// ✅ Option B: Čisto immutable VO (preferované)
final readonly class ProjectWorker {
    // Bez withRole, nový worker pre novú rolu
}
```

### 6. Chýbajúce Domain Exceptions

**Problém**: Používajú sa generické `\DomainException`
```php
// ❌ Zlé
throw new \DomainException('Project not found');
```

**Riešenie**: Špecifické domain exceptions
```php
// ✅ Dobré - src/Project/Domain/Exception/
class ProjectNotFoundException extends DomainException {}
class ProjectAlreadyDeletedException extends DomainException {}
class WorkerAlreadyExistsException extends DomainException {}
```

## 🔧 Menšie vylepšenia

### 7. Event Store implementácia

**Problém**: Hardcoded values v `createAggregate()`
```php
// ❌ Zlé - ProjectEventStoreRepository
protected function createAggregate(): Project {
    return new Project(
        Uuid::generate(), // Random UUID?!
        new ProjectName('Temporary'), // Hardcoded!
        new DateTimeImmutable(),
        Uuid::generate()
    );
}
```

**Riešenie**: Factory pattern alebo reflection
```php
// ✅ Dobré
protected function createAggregate(): Project {
    return Project::createEmpty(); // Factory method
}
```

### 8. HTTP Controller dependency injection

**Problém**: Veľa dependencies v konstruktore
```php
// ❌ Suboptimálne - 7 dependencies
public function __construct(
    private readonly RegisterProjectHandler $registerProjectHandler,
    private readonly GetProjectHandler $getProjectHandler,
    // ... 5 more
) {}
```

**Riešenie**: Command/Query bus pattern
```php
// ✅ Lepšie
public function __construct(
    private readonly CommandBus $commandBus,
    private readonly QueryBus $queryBus,
    private readonly SerializerInterface $serializer
) {}
```

## 📋 Odporúčané akcie (priorita)

### Vysoká priorita
1. **Vytvoriť ProjectRepositoryInterface** v domain layer
2. **Refaktorovať ProjectService** - odstrániť business logic
3. ✅ **HOTOVO: Zjednotiť event handling** - odstránený trait, používa AggregateRoot
4. **Vytvoriť domain exceptions**

### Stredná priorita  
5. **Prehodnotiť ProjectWorker** ako VO vs Entity
6. **Implementovať Command/Query bus**
7. **Opraviť Event Store createAggregate**

### Nízka priorita
8. **Pridať validation** do Command objektov
9. **Implementovať Domain Services** pre komplexné business rules
10. **Pridať integration events** pre komunikáciu medzi bounded contexts

## 🏗️ Cielová architektúra

```
Project/
├── Domain/
│   ├── Model/Project.php (čistý aggregate)
│   ├── ValueObject/ProjectWorker.php (immutable)
│   ├── Repository/ProjectRepositoryInterface.php (contract)
│   ├── Exception/Project*.php (domain exceptions)
│   └── Service/ProjectDomainService.php (complex business rules)
├── Application/
│   ├── Command/*Handler.php (priamo repository dependency)
│   ├── Query/*Handler.php (read models)
│   └── Exception/Application*.php (app exceptions)
└── Infrastructure/
    └── Persistence/ProjectRepository.php (implementuje interface)
```

## 🎯 Výsledok

Po implementácii týchto zmien:
- ✅ Čistá hexagonálna architektúra (domain nezávisí od infra)
- ✅ Správne DDD modelovanie (business logic v domain)
- ✅ **HOTOVO**: Konzistentný event sourcing - duplicitné mechanizmy odstránené
- ✅ Lepšia testovateľnosť
- ✅ Vyššia maintainability

## 🔄 Stav implementácie

### ✅ Vyriešené problémy:
- **#3 Duplicitné event handling mechanizmy** - `ProjectDomainEventsTrait` odstránený, `AggregateRoot` mechanizmus používaný konzistentne

### 🔄 Zostávajúce úlohy:
- #1 Porušenie Dependency Inversion Principle
- #2 Nesprávne umiestnenie business logiky
- #4 Nekonzistentné Command Handler implementácie
- #5-8 Ostatné vylepšenia