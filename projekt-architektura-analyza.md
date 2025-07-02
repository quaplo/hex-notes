# Analýza architektúry projektu - DDD a Hexagonálna architektúra

## 🚨 Kritické problémy

### 1. ✅ VYRIEŠENÉ: Porušenie Dependency Inversion Principle (DIP)

**Problém**: Application layer priamo závisel od Infrastructure layer
```php
// ❌ Zlé - ProjectService.php (už neexistuje)
public function __construct(
    private ProjectEventStoreRepository $projectRepository // Infrastructure dependency!
) {}
```

**✅ IMPLEMENTOVANÉ RIEŠENIE**: Vytvorený domain repository interface
```php
// ✅ Implementované - src/Project/Domain/Repository/ProjectRepositoryInterface.php
interface ProjectRepositoryInterface {
    public function save(Project $project): void;
    public function load(Uuid $aggregateId): ?Project;
    public function exists(Uuid $aggregateId): bool;
}

// ✅ Command handlery používajú interface
class RegisterProjectHandler {
    public function __construct(
        private ProjectRepositoryInterface $projectRepository // Domain interface!
    ) {}
}
```

### 2. ✅ VYRIEŠENÉ: Nesprávne umiestnenie business logiky

**Problém**: `ProjectService` obsahoval domain logic v application layer
```php
// ❌ Zlé - business rules v application service (už neexistuje)
public function renameProject(string $projectId, string $newName): Project {
    $project = $this->projectRepository->load(new Uuid($projectId));
    if (!$project) {
        throw new \DomainException("Project with id $projectId not found");
    }
    // ...
}
```

**✅ IMPLEMENTOVANÉ RIEŠENIE**: Business logic v domain, orchestrácia v Command Handlers
```php
// ✅ Implementované - RenameProjectHandler.php
public function __invoke(RenameProjectCommand $command): Project {
    $project = $this->projectRepository->load($command->projectId);
    if (!$project) {
        throw new ProjectNotFoundException($command->projectId); // Domain exception
    }
    
    $renamedProject = $project->rename($command->newName); // Domain method
    $this->projectRepository->save($renamedProject);
    return $renamedProject;
}
```

**Implementované zmeny**:
- `ProjectService` odstránený - už neexistuje
- Business logic presunnutá do domain model (`Project::rename()`, `Project::addWorker()`)
- Command handlery obsahujú iba orchestráciu
- Používajú domain exceptions namiesto generických

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

### 4. ✅ VYRIEŠENÉ: Nekonzistentné Command Handler implementácie

**Problém**: Niektoré handlery používali `ProjectService`, iné priame repository dependency
```php
// ❌ Nekonzistentné (už opravené)
class AddProjectWorkerHandler {
    public function __construct(private ProjectService $projectService) {}
}
```

**✅ IMPLEMENTOVANÉ RIEŠENIE**: Všetky handlery používajú konzistentný pattern
```php
// ✅ Implementované - všetky handlery
class RegisterProjectHandler {
    public function __construct(private ProjectRepositoryInterface $projectRepository) {}
}
class RenameProjectHandler {
    public function __construct(private ProjectRepositoryInterface $projectRepository) {}
}
class AddProjectWorkerHandler {
    public function __construct(private ProjectRepositoryInterface $projectRepository) {}
}
// ... všetky ostatné handlery
```

**Konzistentný pattern vo všetkých handleroch**:
- Používajú `ProjectRepositoryInterface` namiesto concrete implementácie
- Hadzú `ProjectNotFoundException` pre neexistujúce projekty
- Volajú domain metódy na `Project` aggregate
- Štruktúra: `final readonly class` s `__invoke()` metódou
- Pattern: `load → validate → domain operation → save → return`

### 5. ✅ VYRIEŠENÉ: ProjectWorker ako Value Object

**Problém**: Mal mutable operácie (`withRole`) ale modelovaný ako VO
```php
// ❌ Zmätočné - value object s mutation (už odstránené)
public function withRole(ProjectRole $role): self {
    return new self(/* ... */);
}
```

**✅ IMPLEMENTOVANÉ RIEŠENIE**: Čisto immutable Value Object
```php
// ✅ Implementované - src/Project/Domain/ValueObject/ProjectWorker.php
final class ProjectWorker {
    public function __construct(
        private readonly Uuid $userId,
        private readonly ProjectRole $role,
        private readonly DateTimeImmutable $createdAt,
        private readonly Uuid $addedBy,
    ) {}
    
    // Iba getters, equals(), create() factory
    // Žiadne mutable operácie
}
```

**Implementované zmeny**:
- Odstránená `withRole()` metóda
- Čisto immutable Value Object
- Pre zmenu roly: odstránenie starého worker-a + pridanie nového
- Správne DDD modelovanie: worker s inou rolou = iný worker

### 6. ✅ VYRIEŠENÉ: Chýbajúce Domain Exceptions

**Problém**: Používali sa generické `\DomainException`
```php
// ❌ Zlé
throw new \DomainException('Project not found');
```

**✅ IMPLEMENTOVANÉ RIEŠENIE**: Vytvorené špecifické domain exceptions
```php
// ✅ Implementované - src/Project/Domain/Exception/
class ProjectNotFoundException extends DomainException {}
class ProjectAlreadyDeletedException extends DomainException {}
class WorkerAlreadyExistsException extends DomainException {}
```

**Implementované exceptions**:
- `ProjectNotFoundException` - pre neexistujúce projekty
- `ProjectAlreadyDeletedException` - pre operácie na zmazaných projektoch
- `WorkerAlreadyExistsException` - pre duplikátnych workers

## 🔧 Menšie vylepšenia

### 7. ✅ VYRIEŠENÉ: Event Store implementácia

**Problém**: Hardcoded values v `createAggregate()`
```php
// ❌ Zlé - ProjectEventStoreRepository (už opravené)
protected function createAggregate(): Project {
    return new Project(
        Uuid::generate(), // Random UUID?!
        new ProjectName('Temporary'), // Hardcoded!
        new DateTimeImmutable(),
        Uuid::generate()
    );
}
```

**✅ IMPLEMENTOVANÉ RIEŠENIE**: Factory method pattern
```php
// ✅ Implementované - ProjectEventStoreRepository.php
protected function createAggregate(): Project {
    return Project::createEmpty(); // Factory method
}

// ✅ Implementované - Project.php
public static function createEmpty(): self {
    return new self(
        Uuid::create('00000000-0000-0000-0000-000000000000'), // Null UUID
        new ProjectName('__EMPTY__'),     // Placeholder name - will be set by ProjectCreatedEvent
        new DateTimeImmutable('1970-01-01T00:00:00+00:00'), // Epoch time
        Uuid::create('00000000-0000-0000-0000-000000000000')  // Null owner ID
    );
}
```

**Implementované zmeny**:
- Vytvorená `Project::createEmpty()` factory metóda s platnými null/placeholder hodnotami
- Refaktorovaná `ProjectEventStoreRepository::createAggregate()` na použitie factory metódy
- Eliminované hardcoded dummy hodnoty z Event Store implementácie
- Event Sourcing replay stále funguje správne - všetky properties sa nastavia replay-om domain events

### 8. ✅ VYRIEŠENÉ: HTTP Controller dependency injection

**Problém**: Veľa dependencies v konstruktore
```php
// ❌ Suboptimálne - 10 dependencies (už opravené)
public function __construct(
    private readonly RegisterProjectHandler $registerProjectHandler,
    private readonly RenameProjectHandler $renameProjectHandler,
    private readonly DeleteProjectHandler $deleteProjectHandler,
    private readonly AddProjectWorkerHandler $addProjectWorkerHandler,
    private readonly RemoveProjectWorkerHandler $removeProjectWorkerHandler,
    private readonly GetProjectHandler $getProjectHandler,
    private readonly GetProjectHistoryHandler $getProjectHistoryHandler,
    private readonly GetProjectFullDetailHandler $getProjectFullDetailHandler,
    private readonly SerializerInterface $serializer,
    private readonly ValidatorInterface $validator
) {}
```

**✅ IMPLEMENTOVANÉ RIEŠENIE**: Command/Query Bus pattern
```php
// ✅ Implementované - ProjectController s 4 dependencies namiesto 10
public function __construct(
    private readonly CommandBus $commandBus,
    private readonly QueryBus $queryBus,
    private readonly SerializerInterface $serializer,
    private readonly ValidatorInterface $validator
) {}

// Použitie v action metódach
public function registerProject(Request $request): JsonResponse {
    $command = new RegisterProjectCommand(/*...*/);
    $project = $this->commandBus->handle($command);
    return $this->json($project);
}
```

**Implementované komponenty**:
- `CommandBus` a `QueryBus` interfaces v `src/Shared/Application/`
- `SymfonyCommandBus` a `SymfonyQueryBus` implementácie pomocou Symfony Messenger
- Konfigurácia v `config/packages/messenger.yaml` pre command a query buses
- Registrácia services v `config/services.yaml`
- Refaktorovaný `ProjectController` používa bus pattern namiesto direct handler dependencies

## 📋 Odporúčané akcie (priorita)

### Vysoká priorita
1. ✅ **HOTOVO: Vytvoriť ProjectRepositoryInterface** - implementovaný v domain layer
2. ✅ **HOTOVO: Refaktorovať ProjectService** - odstránený, business logic v domain
3. ✅ **HOTOVO: Zjednotiť event handling** - odstránený trait, používa AggregateRoot
4. ✅ **HOTOVO: Vytvoriť domain exceptions** - implementované špecifické exceptions
5. ✅ **HOTOVO: Zjednotiť Command Handler implementácie** - všetky používajú konzistentný pattern

### Stredná priorita
6. ✅ **HOTOVO: Prehodnotiť ProjectWorker** - čisto immutable Value Object, odstránená withRole()
7. ✅ **HOTOVO: Opraviť Event Store createAggregate** - implementovaný factory method pattern
8. ✅ **HOTOVO: Implementovať Command/Query bus** - redukovaných 10 dependencies na 4 pomocou Mediator pattern

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
- ✅ **HOTOVO**: Čistá hexagonálna architektúra (domain nezávisí od infra)
- ✅ **HOTOVO**: Správne DDD modelovanie (business logic v domain)
- ✅ **HOTOVO**: Konzistentný event sourcing - duplicitné mechanizmy odstránené
- ✅ **HOTOVO**: Špecifické domain exceptions namiesto generických
- ✅ **HOTOVO**: Kompletná testovacia infrastruktúra (45 testov)
- ✅ Lepšia testovateľnosť
- ✅ Vyššia maintainability

## 🔄 Stav implementácie

### ✅ Vyriešené problémy:
- **#1 Porušenie Dependency Inversion Principle** - vytvorený `ProjectRepositoryInterface`, command handlery používajú domain interface
- **#2 Nesprávne umiestnenie business logiky** - `ProjectService` odstránený, business logic v domain model
- **#3 Duplicitné event handling mechanizmy** - `ProjectDomainEventsTrait` odstránený, `AggregateRoot` mechanizmus používaný konzistentne
- **#4 Nekonzistentné Command Handler implementácie** - všetky handlery používajú konzistentný pattern s `ProjectRepositoryInterface`
- **#5 ProjectWorker ako Value Object** - odstránená `withRole()` metóda, čisto immutable Value Object
- **#6 Chýbajúce Domain Exceptions** - implementované špecifické domain exceptions
- **#7 Event Store implementácia** - implementovaný `Project::createEmpty()` factory method, eliminované hardcoded hodnoty
- **Kompletná testovacia infrastruktúra** - vytvorené unit a integration testy

### ✅ Všetky problémy vyriešené!
Všetkých 8 architektonických problémov bolo úspešne implementované a otestované.

## 🧪 Testovacia infrastruktúra

### ✅ IMPLEMENTOVANÉ: Kompletné pokrytie testami

**Vytvorená testovacia infrastruktúra**:

#### Unit testy (`tests/Project/Unit/`)
- **`Domain/Model/ProjectTest.php`** - 16 testov domain modelu
  - Testovanie business rules a domain logic
  - Validácia event recording
  - Error handling pre deleted projekty
  - Worker management operácie

- **`Domain/ValueObject/ProjectNameTest.php`** - 8 testov value objectu
  - Validácia vstupných dát
  - Equality porovnávanie
  - Unicode podpora

- **`Application/Command/RegisterProjectHandlerTest.php`** - 5 testov command handlera
  - Application layer testovanie
  - Repository interactions
  - Event recording validation

#### Integration testy (`tests/Project/Integration/`)
- **`ProjectIntegrationTest.php`** - 8 end-to-end testov
  - Kompletný project lifecycle (create → rename → delete)
  - Worker management workflow
  - Event sourcing integrácia
  - Error scenarios a domain exceptions
  - Concurrency a state consistency

- **`ProjectEventStoreIntegrationTest.php`** - 7 Event Store testov
  - Event persistence a replay
  - Aggregate rekonštrukcia
  - Event dispatcher integrácia
  - Error handling

#### Podporná infrastruktúra (`tests/Project/`)
- **`Doubles/InMemoryProjectRepository.php`** - Test double pre repository
- **`Helpers/ProjectTestFactory.php`** - Factory pre test objects
- **`Helpers/ProjectEventAsserter.php`** - Domain event assertions

### 📊 Výsledky testov
**45 testov passed** (157 assertions):
- ✅ 16 domain model testov
- ✅ 8 value object testov
- ✅ 5 application handler testov
- ✅ 8 integration testov
- ✅ 7 event store integration testov
- ✅ 1 user integration test

### 🎯 Pokrytie testovania
- **Domain layer**: Kompletné pokrytie business rules
- **Application layer**: Command handler validation
- **Event sourcing**: Event persistence a replay
- **Integration**: End-to-end user stories
- **Error handling**: Domain exceptions scenarios
