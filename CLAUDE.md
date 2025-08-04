# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Testing
- `composer test` - Run Pest tests
- `composer test-coverage` - Run tests with coverage report
- `composer test-watch` - Run tests in watch mode

### Code Quality
- `composer phpstan` - Run PHPStan static analysis (level 6)
- `composer phpcs` - Check code style (PSR-12)
- `composer phpcbf` - Auto-fix code style violations
- `composer rector` - Apply Rector refactoring rules
- `composer rector:dry-run` - Preview Rector changes without applying
- `composer deptrac` - Check architectural layer dependencies

### Database
- `composer doctrine:diff` - Generate migration from entity changes
- `composer doctrine:migrate` - Apply pending migrations
- `composer doctrine:sync` - Generate diff and migrate in sequence

### Symfony
- `composer sf:cache-clear` - Clear Symfony cache
- `php bin/console` - Access Symfony console commands

## Architecture Overview

This is a **Symfony 7.2** application implementing **Event Sourcing + CQRS + Hexagonal Architecture + Domain-Driven Design**.

### Key Architectural Patterns

**Event Sourcing**: Aggregates are persisted as sequences of events rather than current state
- Events stored in `DoctrineEventStore` (PostgreSQL)
- `AggregateRoot` base class handles event application and replay
- Full audit trail and temporal queries

**CQRS**: Command and Query Responsibility Segregation
- Separate `command.bus` and `query.bus` via Symfony Messenger
- Commands for state changes, Queries for reads
- Read Models for optimized querying

**Hexagonal Architecture**: Domain isolation from infrastructure
- Domain layer independent of external concerns
- Application layer defines ports (interfaces)
- Infrastructure layer implements adapters

**Domain-Driven Design**: Business logic organized by domain
- Bounded Contexts: `User`, `Project`, `Shared`
- Aggregate Roots with business rules
- Value Objects for data integrity
- Domain Events for decoupled communication

### Directory Structure

```
src/
├── Infrastructure/          # Global infrastructure adapters
├── Project/                 # Project Bounded Context
│   ├── Application/         # Commands, Queries, Handlers
│   ├── Domain/              # Aggregates, Events, Value Objects
│   └── Infrastructure/      # Persistence, Serializers
├── Shared/                  # Cross-domain components
│   ├── Application/         # CQRS buses
│   ├── Domain/              # Base classes (AggregateRoot)
│   ├── Event/               # Event Sourcing interfaces
│   └── ValueObject/         # Common value objects (Email, Uuid)
└── User/                    # User Bounded Context
    └── [same structure as Project]
```

### Dependency Rules (enforced by Deptrac)

- **Domain**: May only depend on Shared components
- **Application**: May depend on Domain + Shared
- **Infrastructure**: May depend on all layers
- **SharedValueObject**: Lowest layer, no dependencies
- Cross-domain communication only through Domain Events

## Testing Strategy

**Framework**: Pest (behavior-driven testing)
- Unit tests in `tests/{Context}/Unit/`
- Integration tests in `tests/{Context}/Integration/`
- Feature tests in `tests/Feature/`

**Test Doubles**: In-memory implementations for unit testing
- `InMemoryEventStore`, `InMemoryProjectRepository`
- Located in `tests/{Context}/Doubles/`

**Helpers**: Domain-specific test utilities
- `ProjectTestFactory` for test data
- `ProjectEventAsserter` for event verification

## Code Quality Tools

### PHPStan (Static Analysis)
- Level 6 configuration in `phpstan.dist.neon`
- Baseline file: `phpstan-baseline.neon`
- Strict type checking enabled

### Rector (Automated Refactoring)
- PHP 8.3 features enabled
- Symfony 7.2 rules applied
- Doctrine ORM rules included
- Configuration in `rector.php`

### PHP CodeSniffer
- PSR-12 coding standard
- Configuration in `phpcs.xml.dist`
- Auto-fix available via `phpcbf`

### Deptrac (Architecture Validation)
- Enforces clean architecture boundaries
- Configuration in `deptrac.yaml`
- Baseline in `deptrac-baseline.yaml`

## Development Workflow

1. **Implementation**: Make changes following DDD patterns
2. **Testing**: `composer test` to verify functionality
3. **Quality**: `composer phpstan && composer phpcs` for standards
4. **Architecture**: `composer deptrac` to validate layer dependencies
5. **Refactoring**: `composer rector:dry-run` for modernization
6. **Database**: `composer doctrine:sync` for schema changes

## Event Sourcing Implementation

### Key Components
- `EventStore` interface with `DoctrineEventStore` implementation
- `AggregateRoot` base class for event-sourced aggregates
- Event serialization via context-specific serializers
- Optimistic concurrency control using aggregate versions

### Event Flow
1. Command → Application Service
2. Load Aggregate from Event Stream
3. Execute Business Logic → Generate Events
4. Save Events to EventStore
5. Dispatch Events for Side Effects

### Database Schema
Events stored in `event_store` table with:
- `aggregate_id`, `event_type`, `event_data`
- `version` for concurrency control
- `occurred_at` timestamp

## Adding New Bounded Contexts

1. Create directory structure: `src/NewContext/{Domain,Application,Infrastructure}/`
2. Update `deptrac.yaml` to include new context in layer definitions
3. Register handlers in `config/services.yaml`
4. Create corresponding test structure in `tests/NewContext/`
5. Add database migrations if read models needed

## Important Notes

- **PHP 8.3+** required with modern features enabled
- **PostgreSQL** recommended for JSON event data extraction
- All aggregates must extend `AggregateRoot`
- Domain Events used for inter-context communication
- Commands and Queries handled via Symfony Messenger
- Read Models built from event projections