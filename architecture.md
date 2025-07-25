# Architektúra PHP Projektu - Analýza

## 1. Architektúra a prístupy

### Použité architektonické štýly

**Event Sourcing + CQRS + Hexagonálna architektúra + Domain-Driven Design**

- **Event Sourcing**: Agregáty sa uchovávajú ako sekvencia eventov namiesto aktuálneho stavu
  - Implementácia cez [`EventStore`](src/Shared/Event/EventStore.php) interface
  - [`DoctrineEventStore`](src/Infrastructure/Persistence/EventStore/DoctrineEventStore.php) ako konkrétna implementácia
  - [`AggregateRoot`](src/Shared/Domain/Model/AggregateRoot.php) obsahuje metódy `apply()`, `replayEvent()` pre event handling

- **CQRS (Command Query Responsibility Segregation)**: 
  - Oddelenie Command a Query busov v [`config/packages/messenger.yaml`](config/packages/messenger.yaml)
  - Commands pre zmeny stavu (napr. [`RegisterProjectCommand`](src/Project/Application/Command/RegisterProjectCommand.php))
  - Queries pre čítanie (napr. [`FindProjectsByOwnerQuery`](src/Project/Application/Query/FindProjectsByOwnerQuery.php))
  - Read Models pre optimalizované čítanie

- **Hexagonálna architektúra**: 
  - Domain layer je nezávislý na infraštruktúre
  - Application layer definuje porty (interfaces)
  - Infrastructure layer implementuje adaptéry

- **Domain-Driven Design**:
  - Bounded Contexts: `User`, `Project`, `Shared`
  - Aggregate Roots: [`User`](src/User/Domain/Model/User.php), `Project`
  - Value Objects: [`Email`](src/Shared/ValueObject/Email.php), [`Uuid`](src/Shared/ValueObject/Uuid.php)
  - Domain Events pre komunikáciu medzi kontextami

### Vstupné a výstupné porty/adaptéry

**Vstupné porty (Application layer interfaces)**:
- [`CommandBus`](src/Shared/Application/CommandBus.php) - port pre commands
- [`QueryBus`](src/Shared/Application/QueryBus.php) - port pre queries
- Command/Query handlers ako aplikačné služby

**Výstupné porty (Domain/Application interfaces)**:
- [`EventStore`](src/Shared/Event/EventStore.php) - ukladanie eventov
- [`EventDispatcher`](src/Shared/Event/EventDispatcher.php) - dispatching eventov
- Repository interfaces v Domain layer

**Adaptéry (Infrastructure implementácie)**:
- [`SymfonyCommandBus`](src/Infrastructure/Bus/SymfonyCommandBus.php) - Symfony Messenger adaptér
- [`DoctrineEventStore`](src/Infrastructure/Persistence/EventStore/DoctrineEventStore.php) - Doctrine adaptér
- [`DomainEventDispatcher`](src/Shared/Infrastructure/Event/DomainEventDispatcher.php) - Symfony event dispatcher adaptér

### Modulárna štruktúra

**Bounded Contexts ako moduly**:
- `User/` - užívateľský kontext
- `Project/` - projektový kontext  
- `Shared/` - zdieľané komponenty

Každý modul má vlastnú Domain/Application/Infrastructure štruktúru.

### Hranice medzi vrstvami

Podľa [`deptrac.yaml`](deptrac.yaml):

- **Domain**: Môže závisieť len na `SharedDomain`, `SharedValueObject`, `SharedEvent`
- **Application**: Môže závisieť na `Domain` + všetkých `Shared*` komponentoch
- **Infrastructure**: Môže závisieť na všetkých vrstvách
- **SharedValueObject**: Najnižšia vrstva bez závislostí

## 2. Štruktúra projektu

### Priečinky v `src/` a ich zodpovednosti

```
src/
├── Infrastructure/          # Globálna infraštruktúra
│   ├── Bus/                # CQRS bus implementácie
│   ├── Event/              # Event handling infraštruktúra
│   ├── Http/               # HTTP adaptéry (controllers, DTOs)
│   └── Persistence/        # Event Store implementácie
├── Project/                # Project Bounded Context
│   ├── Application/        # Application services, handlers
│   ├── Domain/             # Domain model, events, policies
│   └── Infrastructure/     # Context-specific infrastructure
├── Shared/                 # Zdieľané komponenty
│   ├── Application/        # Cross-domain application services
│   ├── Domain/             # Zdieľané domain komponenty
│   ├── Event/              # Event Sourcing interfaces
│   ├── Infrastructure/     # Zdieľané infrastructure
│   └── ValueObject/        # Zdieľané value objects
├── User/                   # User Bounded Context
│   ├── Application/        # User application services
│   ├── Domain/             # User domain model
│   └── Infrastructure/     # User infrastructure
└── Kernel.php              # Symfony kernel
```

### DDD štruktúra

**Entities a Aggregate Roots**:
- [`User`](src/User/Domain/Model/User.php) - User aggregate
- `Project` - Project aggregate
- Oba rozširujú [`AggregateRoot`](src/Shared/Domain/Model/AggregateRoot.php)

**Value Objects**:
- [`Email`](src/Shared/ValueObject/Email.php) - emailová adresa
- [`Uuid`](src/Shared/ValueObject/Uuid.php) - identifikátory
- `ProjectName`, `UserStatus` - kontextovo-špecifické

**Domain Events**:
- `UserDeletedEvent`, `ProjectCreatedEvent`, atď.
- Implementujú [`DomainEvent`](src/Shared/Domain/Event/DomainEvent.php) interface

**Repositories**:
- [`EventStoreRepository`](src/Shared/Event/EventStoreRepository.php) - abstraktná implementácia
- [`AbstractEventStoreRepository`](src/Infrastructure/Persistence/EventStore/AbstractEventStoreRepository.php) - base class

**Application Services**:
- Command Handlers - [`RegisterProjectHandler`](src/Project/Application/Command/RegisterProjectHandler.php)
- Query Handlers - [`FindProjectsByOwnerHandler`](src/Project/Application/Query/FindProjectsByOwnerHandler.php)

**Domain Services**: Implementované ako potreba v Domain layer

## 3. Použité návrhové vzory

### Zoznam vzorov a ich výskyt

**CQRS (Command Query Responsibility Segregation)**:
- Separátne `command.bus` a `query.bus` v [`messenger.yaml`](config/packages/messenger.yaml)
- Commands pre zmeny, Queries pre čítanie
- Read Models pre optimalizované queries

**Event Sourcing**:
- [`AggregateRoot`](src/Shared/Domain/Model/AggregateRoot.php) aplikuje eventy cez `apply()` metódu
- [`EventStore`](src/Shared/Event/EventStore.php) ukladá a načítava eventy
- Replay mechanizmus cez `replayEvent()`

**Repository Pattern**:
- [`AbstractEventStoreRepository`](src/Infrastructure/Persistence/EventStore/AbstractEventStoreRepository.php)
- Interface segregation pre rôzne typy repositories

**Value Object Pattern**:
- [`Email`](src/Shared/ValueObject/Email.php), [`Uuid`](src/Shared/ValueObject/Uuid.php) sú immutable
- Enkapsulujú validáciu a business rules

**Domain Events Pattern**:
- Agregáty produkujú eventy pri business operáciách
- [`EventDispatcher`](src/Shared/Event/EventDispatcher.php) pre publikovanie

**Factory Pattern**:
- `ProjectSnapshotFactory` pre snapshotin
- Static factory methods v aggregates (`User::register()`)

**Composite Pattern**:
- [`CompositeEventSerializer`](src/Infrastructure/Event/CompositeEventSerializer.php) kombinuje viacero serializerov

**Snapshot Pattern**:
- [`FrequencyBasedSnapshotStrategy`](src/Infrastructure/Event/FrequencyBasedSnapshotStrategy.php)
- Optimalizácia pre Event Sourcing

## 4. Technológie a knižnice

### Hlavné závislosti z [`composer.json`](composer.json)

**Symfony 7.2 Framework**:
- `symfony/framework-bundle` - základný framework
- `symfony/messenger` - CQRS buses, message handling
- `symfony/console` - CLI príkazy
- `symfony/validator` - validácia
- `symfony/serializer` - serializácia

**Doctrine ORM 3.5**:
- `doctrine/orm` - ORM pre read models
- `doctrine/doctrine-migrations-bundle` - migrácie DB

**Event Sourcing komponenty**:
- Vlastná implementácia cez [`EventStore`](src/Shared/Event/EventStore.php)
- `ramsey/uuid` - UUID generovanie

**Integrácia v architektúre**:
- **Symfony Messenger**: CQRS implementation s [`SymfonyCommandBus`](src/Infrastructure/Bus/SymfonyCommandBus.php)
- **Doctrine**: Event store a read models
- **Symfony DI**: Dependency injection podľa [`services.yaml`](config/services.yaml)

## 5. Kvalita kódu a nástroje

### Statická analýza a transformácia

**PHPStan level 6** ([`phpstan.dist.neon`](phpstan.dist.neon)):
- Vysoko prísna analýza typu
- Pokrýva `src/` a `tests/`
- Baseline súbor: `phpstan-baseline.neon`

**Rector** ([`rector.php`](rector.php)):
- PHP 8.3 features: `LevelSetList::UP_TO_PHP_83`
- Code quality: `SetList::CODE_QUALITY`, `SetList::DEAD_CODE`
- Symfony rules: `SymfonySetList::SYMFONY_72`
- Doctrine rules: `DoctrineSetList::DOCTRINE_ORM_214`

**PHP CodeSniffer** ([`phpcs.xml.dist`](phpcs.xml.dist)):
- PSR-12 coding standard
- Pokrýva `src/` a `tests/`

**Deptrac** ([`deptrac.yaml`](deptrac.yaml)):
- Architektonické vrstvy a ich obmedzenia
- Baseline: `deptrac-baseline.yaml`

### Deptrac vrstvy a obmedzenia

**Definované vrstvy**:
1. `Domain` - kontextové domain vrstvy (`src/(Project|User)/Domain`)
2. `Application` - kontextové application vrstvy (`src/(Project|User)/Application`)  
3. `Infrastructure` - infraštruktúra (`src/(Project|User)/Infrastructure`, `src/Infrastructure`)
4. `SharedDomain`, `SharedApplication`, `SharedInfrastructure` - zdieľané komponenty
5. `SharedValueObject` - najnižšia vrstva
6. `SharedEvent` - event sourcing komponenty

**Kľúčové obmedzenia**:
- Domain závisí len na Shared komponentoch
- SharedValueObject nemá žiadne závislosti  
- Infrastructure môže závisieť na všetkých vrstvách
- Prísne dodržiavanie Dependency Inversion Principle

## 6. Testovanie

### Testovací framework: Pest

**Konfigurácia** ([`phpunit.dist.xml`](phpunit.dist.xml)):
- Bootstrap: `tests/bootstrap.php`
- Cache: `.phpunit.cache`
- Pokrytie: `src/` directory

**Štruktúra testov**:
```
tests/
├── Debug/              # Debug utility tests
├── Feature/            # Feature/integration tests
├── Integration/        # Integration tests
├── Postman/           # API tests
├── Project/           # Project context tests
│   ├── Application/   # Application layer tests
│   ├── Doubles/       # Test doubles/mocks
│   ├── Helpers/       # Test helpers and factories
│   ├── Integration/   # Integration tests
│   └── Unit/          # Unit tests
├── Shared/            # Shared component tests
├── Unit/              # Global unit tests
└── User/              # User context tests
```

### Test approach

**Domain-driven testing** ([`ProjectTest.php`](tests/Project/Unit/Domain/Model/ProjectTest.php)):
- Behavior-driven test descriptions: `describe()` a `test()`
- Test factories: `ProjectTestFactory` pre test data
- Event assertions: `ProjectEventAsserter` pre domain events
- Pest syntax namiesto tradičného PHPUnit

**Test pokrytie**: Stratégia zahŕňa unit, integration a feature testy s dôrazom na domain logic.

## 7. Dev workflow a príkazy

### Skripty z [`composer.json`](composer.json)

**Testovanie**:
- `test` - spustí Pest tests (`vendor/bin/pest`)
- `test-coverage` - tests s pokrytím (`vendor/bin/pest --coverage`) 
- `test-watch` - watch mode (`vendor/bin/pest --watch`)

**Kvalita kódu**:
- `phpstan` - statická analýza (`vendor/bin/phpstan analyse`)
- `phpcs` - code style check (`vendor/bin/phpcs`)
- `phpcbf` - auto-fix code style (`vendor/bin/phpcbf`)

**Refactoring**:
- `rector` - aplikuje zmeny (`vendor/bin/rector`)
- `rector:dry-run` - preview zmien (`vendor/bin/rector --dry-run`)
- `rector:check` - check bez diffs (`vendor/bin/rector --dry-run --no-diffs`)

**Databáza**:
- `doctrine:diff` - generuje migrácie (`php bin/console doctrine:migrations:diff`)
- `doctrine:migrate` - aplikuje migrácie (`php bin/console doctrine:migrations:migrate`)
- `doctrine:sync` - diff + migrate v sekvencii

**Architektúra**:
- `deptrac` - kontrola architektonických obmedzení (`vendor/bin/deptrac analyse`)
- `deptrac:baseline` - generuje baseline (`vendor/bin/deptrac analyse --generate-baseline`)
- `deptrac:debug` - debug vrstiev (`vendor/bin/deptrac debug:layer`)

**Symfony**:
- `sf:cache-clear` - vyčistí cache (`php bin/console cache:clear`)

### Typický dev workflow

1. **Úprava kódu** - implementácia feature/bugfix
2. **Testovanie** - `composer test` pre overenie funkcionality
3. **Kvalita** - `composer phpstan` + `composer phpcs` pre štandardy
4. **Refactoring** - `composer rector:dry-run` pre modern PHP
5. **Architektúra** - `composer deptrac` pre závislosti
6. **Migrácie** - `composer doctrine:sync` pri DB zmenách
7. **Commit** - po úspešnom prechode všetkých kontrol

## 8. Šablóna pre nový projekt

### Adresárová štruktúra

```
project-name/
├── bin/console                 # Symfony console
├── config/                     # Konfigurácie
│   ├── packages/              # Symfony packages config
│   └── services.yaml          # DI container
├── migrations/                # Doctrine migrations
├── public/index.php           # Web entry point
├── src/                       # Aplikačný kód
│   ├── Infrastructure/        # Globálna infraštruktúra
│   ├── NewContext/           # Nový bounded context
│   │   ├── Application/      # Application layer
│   │   ├── Domain/           # Domain layer  
│   │   └── Infrastructure/   # Context infrastructure
│   ├── Shared/               # Zdieľané komponenty
│   └── Kernel.php            # Symfony kernel
├── tests/                    # Testy
├── composer.json             # Dependencies a skripty
├── deptrac.yaml             # Architektonické obmedzenia
├── phpstan.dist.neon        # Statická analýza
├── rector.php               # Refactoring rules
└── phpcs.xml.dist           # Code style
```

### Povinné nástroje a nastavenia

**PHP 8.3+** s rozšíreniami:
- `ext-ctype`, `ext-iconv`

**Composer dependencies**:
```json
{
    "require": {
        "symfony/framework-bundle": "7.2.*",
        "symfony/messenger": "7.2.*", 
        "doctrine/orm": "^3.5",
        "ramsey/uuid": ">=4.9"
    },
    "require-dev": {
        "pestphp/pest": "^3.8",
        "phpstan/phpstan": "^2.1",
        "rector/rector": "dev-main",
        "qossmic/deptrac": "^2.0",
        "squizlabs/php_codesniffer": "^3.13"
    }
}
```

**Konfiguračné súbory**:
- Nakopírovať `deptrac.yaml`, `phpstan.dist.neon`, `rector.php`, `phpcs.xml.dist`
- Upraviť vrstvy v `deptrac.yaml` podľa nových kontextov
- Nastaviť `config/services.yaml` pre DI container
- Nastaviť `config/packages/messenger.yaml` pre CQRS

**Databáza setup**:
- PostgreSQL (kvôli JSON extraction v EventStore)
- Vytvoriť migrácie pre `event_store` tabuľku
- Nastaviť `DATABASE_URL` v `.env`

**Development workflow**:
1. `composer install` - inštalácia závislostí
2. `php bin/console doctrine:database:create` - vytvorenie DB
3. `php bin/console doctrine:migrations:migrate` - aplikácia migrácií
4. `composer test` - spustenie testov
5. `composer phpstan` - statická analýza  
6. `composer deptrac` - kontrola architektúry

**CI/CD pipeline** by mal spúšťať:
```bash
composer test-coverage
composer phpstan  
composer phpcs
composer rector:check
composer deptrac
```

### Vytvorenie nového bounded contextu

1. **Adresár** - `src/NewContext/{Application,Domain,Infrastructure}/`
2. **Deptrac** - pridať do `deptrac.yaml` layers a rules
3. **Services** - zaregistrovať handlers v `config/services.yaml`
4. **Testy** - `tests/NewContext/{Unit,Integration}/`
5. **Migrácie** - prípadné nové read models


---

## 9. Praktický návod: Nový projekt s Order doménou

### Krok 1: Základný Symfony setup

```bash
# Vytvorenie nového Symfony projektu
composer create-project symfony/skeleton order-management
cd order-management

# Inštalácia základných závislostí
composer require symfony/framework-bundle:7.2.*
composer require symfony/messenger:7.2.*
composer require doctrine/orm:^3.5
composer require doctrine/doctrine-migrations-bundle:^3.4
composer require ramsey/uuid:>=4.9

# Dev závislosti
composer require --dev pestphp/pest:^3.8
composer require --dev phpstan/phpstan:^2.1
composer require --dev rector/rector:dev-main
composer require --dev qossmic/deptrac:^2.0
composer require --dev squizlabs/php_codesniffer:^3.13
```

### Krok 2: Kopírovanie a adaptácia Shared komponentov

Najjednoduchšie je skopírovať celý `src/Shared/` priečinok z aktuálneho projektu:

```bash
# Skopíruj z aktuálneho projektu
cp -r /cesta/k/hex-notes/src/Shared src/
cp -r /cesta/k/hex-notes/src/Infrastructure src/
```

**Súbory na kopírovanie** (zachovaj štruktúru):
- `src/Shared/` - celý priečinok
- `src/Infrastructure/Bus/` - CQRS implementácia  
- `src/Infrastructure/Event/` - Event infrastructure
- `src/Infrastructure/Persistence/EventStore/` - Event Store
- `src/Infrastructure/Http/Controller/BaseController.php` - base controller

### Krok 3: Vytvorenie Order domény

```bash
mkdir -p src/Order/{Domain/{Model,Event,ValueObject,Repository,Exception},Application/{Command,Query,EventHandler},Infrastructure/{Event,Persistence,Mapper}}
```

**Minimálna Order doména** - `src/Order/Domain/Model/Order.php`:
```php
<?php
declare(strict_types=1);

namespace App\Order\Domain\Model;

use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;
use App\Order\Domain\Event\OrderCreatedEvent;
use App\Order\Domain\ValueObject\OrderStatus;
use DateTimeImmutable;

final class Order extends AggregateRoot
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $customerId,
        private OrderStatus $status,
        private readonly DateTimeImmutable $createdAt
    ) {}

    public static function create(Uuid $customerId): self
    {
        $order = new self(
            Uuid::generate(),
            $customerId,
            OrderStatus::PENDING,
            new DateTimeImmutable()
        );
        
        $order->apply(OrderCreatedEvent::create($order->id, $customerId));
        return $order;
    }

    public function getId(): Uuid { return $this->id; }
    public function getCustomerId(): Uuid { return $this->customerId; }
    public function getStatus(): OrderStatus { return $this->status; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }

    protected function handleEvent(DomainEvent $domainEvent): void
    {
        match ($domainEvent::class) {
            OrderCreatedEvent::class => $this->handleOrderCreated($domainEvent),
            default => throw new \RuntimeException('Unknown event: ' . $domainEvent::class)
        };
    }

    private function handleOrderCreated(OrderCreatedEvent $event): void
    {
        // State už je nastavený v konštruktore
    }
}
```

### Krok 4: Konfiguračné súbory

**Skopíruj a uprav**:
- `config/services.yaml` - uprav pre Order kontext
- `config/packages/messenger.yaml` - zachovaj
- `config/packages/doctrine.yaml` - uprav mappings
- `deptrac.yaml` - uprav vrstvy pre Order
- `phpstan.dist.neon`, `rector.php`, `phpcs.xml.dist` - zachovaj

**Upravený `config/services.yaml`** (zjednodušená verzia):
```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    App\:
        resource: '../src/'
        exclude:
            - '../src/DependencyInjection/'
            - '../src/Kernel.php'

    # CQRS Buses
    App\Shared\Application\CommandBus: '@App\Infrastructure\Bus\SymfonyCommandBus'
    App\Shared\Application\QueryBus: '@App\Infrastructure\Bus\SymfonyQueryBus'
    

### Alternatívny prístup: Branch + cleanup (ODPORÚČANÉ)

**Ešte jednoduchší spôsob** je vytvoriť novú branch a odstrániť nepotrebné domény:

```bash
# Vytvorenie novej branch pre Order projekt
git checkout -b order-management-clean
git push -u origin order-management-clean

# Odstránenie User a Project domén
rm -rf src/User/
rm -rf src/Project/
rm -rf tests/User/
rm -rf tests/Project/

# Vyčistenie migrácií (zachovaj len event_store migrácie)
rm migrations/Version202*User*
rm migrations/Version202*Project*
```

### Úprava konfiguračných súborov

**`config/services.yaml` - odstráň User/Project sekcie**:
```yaml
# Odstráň tieto sekcie:
# - User Command/Query Handlers
# - Project Command/Query Handlers  
# - User Event Handlers
# - Project Event Handlers
# - Project Event Serializers
# - Project-specific interfaces

# Zachovaj len:
services:
    _defaults:
        autowire: true
        autoconfigure: true

    App\:
        resource: '../src/'
        exclude:
            - '../src/DependencyInjection/'
            - '../src/Kernel.php'

    # CQRS Buses
    App\Shared\Application\CommandBus: '@App\Infrastructure\Bus\SymfonyCommandBus'
    App\Shared\Application\QueryBus: '@App\Infrastructure\Bus\SymfonyQueryBus'
    
    # Event Sourcing Core
    App\Shared\Event\EventStore: '@App\Infrastructure\Persistence\EventStore\DoctrineEventStore'
    App\Shared\Event\EventDispatcher: '@App\Shared\Infrastructure\Event\DomainEventDispatcher'
```

**`deptrac.yaml` - aktualizuj pre Order kontext**:
```yaml
parameters:
    paths:
        - ./src
    layers:
        # Order Domain
        - name: Domain
          collectors:
              - type: directory
                value: 'src/Order/Domain'
        
        # Order Application  
        - name: Application
          collectors:
              - type: directory
                value: 'src/Order/Application'
        
        # Order Infrastructure
        - name: Infrastructure
          collectors:
              - type: directory
                value: 'src/Order/Infrastructure'
              - type: directory
                value: 'src/Infrastructure'
        
        # Shared components (nezmenené)
        - name: SharedDomain
          collectors:
              - type: directory
                value: 'src/Shared/Domain'
        # ... ostatné Shared vrstvy
```

**`config/packages/doctrine.yaml` - odstráň User/Project mappings**:
```yaml
doctrine:
    orm:
        mappings:
            App:
                is_bundle: false
                type: attribute
                dir: '%kernel.project_dir%/src/Infrastructure/Persistence/Doctrine/Entity'
                prefix: 'App\Infrastructure\Persistence\Doctrine\Entity'
                alias: App
            # Odstráň User a Project mappings
            # Pridaj Order mapping keď budeš mať read models
```

### Výhody branch prístupu:

✅ **Zachová Git históriu** - všetky commity a development práca  
✅ **Funkčné nástroje** - phpstan, rector, deptrac už nakonfigurované  
✅ **Otestovaná infraštruktúra** - Event Sourcing, CQRS plne funkčné  
✅ **Čistý start** - žiadne legacy User/Project kódy  
✅ **Rýchly setup** - 15 minút namiesto hodín  

### Porovnanie prístupov:

| Aspekt | Nový projekt | Branch cleanup |
|--------|-------------|---------------|
| Čas setup | 4-6 hodín | 15-30 minút |
| Git história | ❌ Stráca sa | ✅ Zachováva |
| Konfigurácie | 🔄 Manuálne kopírovanie | ✅ Hotové |
| Testovanie | 🔄 Treba nanovo | ✅ Infraštruktúra testovaná |
| Riziká | 🔴 Chyby pri kopírovaní | 🟢 Minimálne |

**Odporúčanie**: Branch prístup je jednoznačne lepší - rýchlejší, bezpečnejší a zachováva všetku hodnotu už vykonanej práce.
    # Event Sourcing
    App\Shared\Event\EventStore: '@App\Infrastructure\Persistence\EventStore\DoctrineEventStore'
    App\Shared\Event\EventDispatcher: '@App\Shared\Infrastructure\Event\DomainEventDispatcher'
    
    # Order Command Handlers
    App\Order\Application\Command\:
        resource: '../src/Order/Application/Command/*Handler.php'
        tags: [{ name: messenger.message_handler, bus: command.bus }]
```

### Krok 5: Databáza a migrácie

```bash
# Vytvorenie databázy
php bin/console doctrine:database:create

# Vytvorenie migrácie pre event_store
php bin/console doctrine:migrations:diff

# Aplikovanie migrácií  
php bin/console doctrine:migrations:migrate
```

**Event store migrácia** (vytvor manuálne ak treba):
```sql
CREATE TABLE event_store (
    id SERIAL PRIMARY KEY,
    aggregate_id VARCHAR(36) NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    event_data TEXT NOT NULL,
    version INTEGER NOT NULL,
    occurred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    UNIQUE(aggregate_id, version)
);
```

### Krok 6: Prvý test

**`tests/Order/Unit/Domain/Model/OrderTest.php`**:
```php
<?php
declare(strict_types=1);

use App\Order\Domain\Model\Order;
use App\Shared\ValueObject\Uuid;

describe('Order Domain Model', function (): void {
    test('order can be created', function (): void {
        $customerId = Uuid::generate();
        $order = Order::create($customerId);
        
        expect($order->getId())->toBeInstanceOf(Uuid::class);
        expect($order->getCustomerId()->equals($customerId))->toBeTrue();
        expect($order->getStatus()->isPending())->toBeTrue();
        expect($order->getDomainEvents())->toHaveCount(1);
    });
});
```

### Krok 7: Spustenie testov a kontrol

```bash
# Composer skripty
composer test
composer phpstan  
composer deptrac
```

### Časový harmonogram (1-2 dni)

**Deň 1 (setup)**:
1. Symfony install + závislosti (30 min)
2. Kopírovanie Shared komponentov (1 hod)
3. Základná Order doména (2 hod)
4. Konfigurácia (1 hod)

**Deň 2 (rozšírenie)**:
1. Command/Query handlers (2 hod)
2. Testy a quality tools (2 hod)
3. Prvé API endpointy (2 hod)

### Najčastejšie úskalia

1. **Namespace konflikty** - skontroluj `App\` namespace v autoload
2. **Doctrine mappings** - uprav cesty v `doctrine.yaml`
3. **Deptrac rules** - aktualizuj `deptrac.yaml` pre Order kontext
4. **Event serializers** - vytvor `OrderEventSerializer`

### Odporúčané kroky po základnom setup

1. **Value Objects**: `OrderStatus`, `OrderNumber`, `Money`
2. **Domain Events**: `OrderCreatedEvent`, `OrderCancelledEvent`
3. **Commands**: `CreateOrderCommand`, `CancelOrderCommand`
4. **Queries**: `FindOrderQuery`, `FindOrdersByCustomerQuery`
5. **Read Models**: Pre optimalizované queries
6. **API Controllers**: HTTP adaptéry pre commands/queries

Takto môžeš začať s minimálnou, ale plne funkčnou Order doménou a postupne rozširovať.
Táto architektúra poskytuje solídny základ pre modifikovateľné, testovateľné a maintainable aplikácie s jasným separation of concerns a prísnym dodržiavaním architectural patterns.