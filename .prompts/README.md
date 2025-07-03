# AI Assistant Prompts for DDD/Event Sourcing Project

Táto zložka obsahuje kolekciu promptov pre AI asistenta, ktoré definujú metodiky, štandardy a architektonické vzory používané v tomto projekte.

## Účel

Tieto prompty slúžia na:
- Udržanie konzistentnosti kódu a architektúry
- Dodržiavanie DDD, SOLID a ďalších princípov
- Zabezpečenie čistého kódu a testovateľnosti
- Vzdelávanie o pokročilých design patterns
- Riadenie separácie domén a bounded contexts

## Štruktúra promptov

### 📐 Architecture (`architecture/`)
Základné architektonické princípy a vzory:
- **ddd-principles.md** - Domain Driven Design fundamenty
- **hexagonal-architecture.md** - Hexagonálna architektúra a separácia vrstiev
- **event-sourcing.md** - Event Sourcing implementácia a pravidlá
- **cqrs-patterns.md** - Command Query Responsibility Segregation

### 🎯 Code Quality (`code-quality/`)
Štandardy kvality kódu:
- **solid-principles.md** - SOLID princípy a ich aplikácia
- **testing-standards.md** - TDD, unit testing a integration testing
- **php-standards.md** - PHP 8.2+ špecifické štandardy
- **naming-conventions.md** - Konvencie pre pomenovanie

### 🏗️ Domain Modeling (`domain-modeling/`)
Návrh doménových modelov:
- **aggregate-design.md** - Návrh agregátov a ich boundaries
- **value-objects.md** - Value objekty a ich implementácia
- **domain-events.md** - Domain eventy a event handling
- **repository-patterns.md** - Repository pattern a persistence

### ⚙️ Application Layer (`application-layer/`)
Aplikačná vrstva:
- **command-handlers.md** - Command handling a CQRS
- **query-handlers.md** - Query handling a read models
- **cross-domain-communication.md** - Komunikácia medzi doménami
- **use-cases.md** - Use case implementácia

### 🔧 Integration (`integration/`)
Integrácia s frameworkmi:
- **symfony-integration.md** - Symfony špecifické vzory
- **doctrine-patterns.md** - Doctrine ORM best practices
- **infrastructure-layer.md** - Infrastructure adapters

## Technický stack

- **PHP**: 8.2+
- **Framework**: Symfony 7.2
- **ORM**: Doctrine 3.3
- **Testing**: Pest PHP
- **Architecture**: DDD + Hexagonal + Event Sourcing + CQRS

## Použitie

Pred pridaním nového kódu AI asistent by mal:
1. Prečítať relevantné prompty z príslušnej kategórie
2. Skontrolovať súlad s existujúcimi vzormi
3. Aplikovať princípy na novú implementáciu
4. Zabezpečiť testovateľnosť
5. Dodržať bounded context boundaries