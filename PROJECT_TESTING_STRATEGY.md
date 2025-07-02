# Stratégia testovania - Project Domain

## 📋 Prehľad testovacích vrstiev

Náš testing framework: **Pest** s PHPUnit backend
Architektúra: **DDD + Hexagonálna + Event Sourcing**

```
tests/
├── Unit/                    # Jednotkové testy (izolované komponenty)
├── Integration/             # Integračné testy (komponenty + infraštruktúra)  
├── Application/             # Application layer testy
├── Domain/                  # Domain business rules testy
├── Feature/                 # End-to-end feature testy
└── Project/                 # Všetky Project domain testy
    ├── Unit/
    ├── Integration/
    ├── Application/
    ├── Domain/
    └── Feature/
```

## 🎯 Ciele testovania

### 1. **Zabezpečenie kvality**
- 90%+ code coverage pre Domain layer
- 80%+ code coverage pre Application layer  
- 70%+ pre Infrastructure layer

### 2. **Dokumentácia správania**
- Každý test = living documentation
- Business rules jasne definované v testoch
- Event sourcing scenáre pokryté

### 3. **Regresia protection**
- Bezpečný refactoring
- Zachytenie breaking changes
- CI/CD pipeline protection

## 🏗️ Testing Pyramid pre Project Domain

### Level 1: Unit Tests (70%) - Najrýchlejšie
**Testujú jednotlivé komponenty v izolácii**

#### Domain Model Tests
```php
// tests/Project/Unit/Domain/Model/ProjectTest.php
test('project can be created with valid data')
test('project creation records ProjectCreatedEvent')
test('project can be renamed')
test('project rename records ProjectRenamedEvent') 
test('deleted project cannot be renamed')
test('project can be deleted')
test('project deletion records ProjectDeletedEvent')
test('already deleted project cannot be deleted again')
```

#### Value Object Tests
```php
// tests/Project/Unit/Domain/ValueObject/ProjectNameTest.php
test('project name can be created with valid string')
test('project name throws exception for empty string')
test('project name throws exception for too long string')
test('project names are equal when values match')

// tests/Project/Unit/Domain/ValueObject/ProjectWorkerTest.php
test('project worker can be created')
test('project worker equality works correctly')
test('project worker is immutable')
```

#### Domain Event Tests
```php
// tests/Project/Unit/Domain/Event/ProjectCreatedEventTest.php
test('project created event contains correct data')
test('project created event has occurred at timestamp')
test('project created event serialization works')
```

#### Domain Exception Tests
```php
// tests/Project/Unit/Domain/Exception/ProjectNotFoundExceptionTest.php
test('project not found exception contains project id')
test('project already deleted exception has correct message')
```

### Level 2: Integration Tests (20%) - Stredne rýchle
**Testujú interakciu komponentov s infraštruktúrou**

#### Event Store Repository Tests
```php
// tests/Project/Integration/Infrastructure/ProjectEventStoreRepositoryTest.php
test('project can be saved and loaded from event store')
test('project events are properly stored')
test('project loading replays events correctly')
test('concurrent modification is detected')
test('event dispatching works after save')
```

#### Database Integration Tests
```php
// tests/Project/Integration/Infrastructure/ProjectDatabaseTest.php
test('events are persisted to database')
test('event store handles database failures gracefully')
test('event versioning works correctly')
```

### Level 3: Application Tests (15%) - Aplikačná logika
**Testujú Application layer bez infraštruktúry**

#### Command Handler Tests
```php
// tests/Project/Application/Command/RegisterProjectHandlerTest.php
test('register project handler creates new project')
test('register project handler saves project via repository')
test('register project handler returns created project')

// tests/Project/Application/Command/RenameProjectHandlerTest.php  
test('rename project handler loads existing project')
test('rename project handler renames project')
test('rename project handler saves renamed project')
test('rename project handler throws exception for non-existent project')

// tests/Project/Application/Command/DeleteProjectHandlerTest.php
test('delete project handler deletes existing project')
test('delete project handler throws exception for already deleted project')
```

#### Query Handler Tests
```php
// tests/Project/Application/Query/GetProjectHandlerTest.php
test('get project handler returns project data')
test('get project handler throws exception for non-existent project')

// tests/Project/Application/Query/GetProjectHistoryHandlerTest.php
test('get project history handler returns event history')
test('get project history handler returns empty for non-existent project')
```

### Level 4: Domain Tests (10%) - Business rules
**Testujú komplexné domain scenáre**

#### Aggregate Behavior Tests
```php
// tests/Project/Domain/Aggregate/ProjectAggregateTest.php
test('project aggregate handles complete lifecycle')
test('project aggregate maintains consistency during worker operations')
test('project aggregate prevents invalid state transitions')
test('project aggregate event sourcing reconstruction works')
```

#### Business Rules Tests
```php
// tests/Project/Domain/BusinessRules/ProjectBusinessRulesTest.php
test('owner cannot be removed as worker')
test('duplicate workers cannot be added')
test('deleted project operations are blocked')
test('project name uniqueness is enforced') // ak implementujeme
```

### Level 5: Feature Tests (5%) - Najpomalšie
**End-to-end scenáre cez HTTP API**

#### Project Management Features
```php
// tests/Project/Feature/ProjectManagementTest.php
test('complete project lifecycle via API')
    // POST /projects - create
    // GET /projects/{id} - read  
    // PUT /projects/{id} - rename
    // DELETE /projects/{id} - delete

test('project worker management via API')
    // POST /projects/{id}/workers - add worker
    // DELETE /projects/{id}/workers/{userId} - remove worker
    // GET /projects/{id}/workers - list workers
```

## 🛠️ Testovací toolkit

### Test Utilities
```php
// tests/Project/Helpers/ProjectTestFactory.php
class ProjectTestFactory
{
    public static function createProject(array $overrides = []): Project
    public static function createProjectName(string $name = 'Test Project'): ProjectName  
    public static function createProjectWorker(array $overrides = []): ProjectWorker
    public static function createValidCommand(array $overrides = []): RegisterProjectCommand
}

// tests/Project/Helpers/ProjectEventAsserter.php
class ProjectEventAsserter
{
    public static function assertProjectCreatedEvent(DomainEvent $event, Uuid $id, ProjectName $name)
    public static function assertProjectRenamedEvent(DomainEvent $event, ProjectName $oldName, ProjectName $newName)
    public static function assertEventCount(array $events, int $expectedCount)
}
```

### Mock Objects
```php
// tests/Project/Doubles/InMemoryProjectRepository.php
class InMemoryProjectRepository implements ProjectRepositoryInterface
{
    private array $projects = [];
    
    public function save(Project $project): void
    public function load(Uuid $id): ?Project
    public function findAll(): array
}

// tests/Project/Doubles/FakeEventDispatcher.php  
class FakeEventDispatcher implements EventDispatcher
{
    private array $dispatchedEvents = [];
    
    public function dispatch(array $events): void
    public function getDispatchedEvents(): array
    public function clearEvents(): void
}
```

## 📊 Testing Coverage Goals

### Pokrytie kódu (Code Coverage)
- **Domain Model**: 95%+ (kritické business rules)
- **Application Handlers**: 90%+ (application logic)
- **Infrastructure**: 75%+ (integration points)  
- **Controllers**: 80%+ (API contracts)

### Pokrytie scenárov (Scenario Coverage)
- **Happy path**: 100% (všetky úspešné prípady)
- **Error cases**: 95% (výnimky a chybové stavy)
- **Edge cases**: 85% (hraničné prípady)
- **Integration**: 80% (interakcie medzi komponentmi)

## 🚀 Implementačný plán

### Fáza 1: Foundation (Týždeň 1)
1. ✅ Vytvoriť testing stratégiu
2. 📋 Nastaviť test helper classes
3. 📋 Implementovať ProjectTestFactory
4. 📋 Vytvoriť InMemoryProjectRepository
5. 📋 Nastaviť základné test suites

### Fáza 2: Domain Tests (Týždeň 2)  
1. 📋 Project aggregate unit tests
2. 📋 ProjectWorker value object tests
3. 📋 ProjectName value object tests
4. 📋 Domain events tests
5. 📋 Domain exceptions tests

### Fáza 3: Application Tests (Týždeň 3)
1. 📋 Command handler tests (všetky)
2. 📋 Query handler tests (všetky) 
3. 📋 Application service tests
4. 📋 Event handler tests

### Fáza 4: Integration Tests (Týždeň 4)
1. 📋 Event store repository tests
2. 📋 Database integration tests
3. 📋 Event dispatching tests
4. 📋 Complete workflow tests

### Fáza 5: Feature Tests (Týždeň 5)
1. 📋 HTTP API tests
2. 📋 End-to-end scenarios
3. 📋 Performance tests
4. 📋 Security tests

## ⚡ Quick Start Commands

```bash
# Spustiť všetky Project testy
composer test tests/Project

# Spustiť len unit testy  
composer test tests/Project/Unit

# Spustiť s coverage reportom
composer test-coverage tests/Project

# Spustiť konkrétny test
composer test tests/Project/Unit/Domain/Model/ProjectTest.php

# Watch mode pre development
composer test-watch tests/Project
```

## 🎯 Success Metrics

### Kvalitatívne
- [ ] Všetky business rules sú pokryté testmi
- [ ] Regression bugs sa zachytia automaticky  
- [ ] Refactoring je bezpečný s test suiteom
- [ ] Nový developer chápe domain cez testy

### Kvantitatívne  
- [ ] 90%+ code coverage na Domain layer
- [ ] <100ms average test execution time
- [ ] 0 flaky tests (nepredvídateľné výsledky)
- [ ] 100% CI/CD pipeline reliability

---

*Tento dokument bude postupne aktualizovaný ako living documentation našej testing stratégie.*