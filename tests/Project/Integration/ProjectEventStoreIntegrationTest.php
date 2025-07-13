<?php

declare(strict_types=1);

use App\Project\Application\Command\RenameProjectCommand;
use App\Project\Application\Command\AddProjectWorkerCommand;
use App\Infrastructure\Persistence\EventStore\ProjectEventStoreRepository;
use App\Project\Application\Command\RegisterProjectHandler;
use App\Project\Application\Command\RenameProjectHandler;
use App\Project\Application\Command\AddProjectWorkerHandler;
use App\Project\Domain\Event\ProjectCreatedEvent;
use App\Project\Domain\Event\ProjectRenamedEvent;
use App\Project\Domain\Event\ProjectWorkerAddedEvent;
use App\Project\Domain\Model\ProjectSnapshotFactory;
use App\Tests\Project\Doubles\InMemoryEventStore;
use App\Tests\Project\Doubles\InMemoryEventDispatcher;
use App\Tests\Project\Doubles\InMemorySnapshotStore;
use App\Tests\Project\Doubles\InMemorySnapshotStrategy;
use App\Tests\Project\Helpers\ProjectTestFactory;

describe('Project Event Store Integration Tests', function (): void {

    beforeEach(function (): void {
        $this->eventStore = new InMemoryEventStore();
        $this->eventDispatcher = new InMemoryEventDispatcher();
        $this->snapshotStore = new InMemorySnapshotStore();
        $this->snapshotFactory = new ProjectSnapshotFactory();
        $this->snapshotStrategy = new InMemorySnapshotStrategy();

        $this->repository = new ProjectEventStoreRepository(
            $this->eventStore,
            $this->eventDispatcher,
            $this->snapshotStore,
            $this->snapshotFactory,
            $this->snapshotStrategy
        );

        $this->registerHandler = new RegisterProjectHandler($this->repository);
        $this->renameHandler = new RenameProjectHandler($this->repository);
        $this->addWorkerHandler = new AddProjectWorkerHandler($this->repository);
    });

    describe('Event Store Operations', function (): void {

        test('project creation stores events in event store', function (): void {
            $registerProjectCommand = ProjectTestFactory::createValidRegisterProjectCommand([
                'name' => 'Event Store Test'
            ]);

            $project = ($this->registerHandler)($registerProjectCommand);

            // Verify project was saved
            expect($this->repository->exists($project->getId()))->toBeTrue();

            // Verify events were stored
            $storedEvents = $this->eventStore->getEvents($project->getId());
            expect($storedEvents)->toHaveCount(1);
            expect($storedEvents[0])->toBeInstanceOf(ProjectCreatedEvent::class);

            // Verify events were dispatched
            $dispatchedEvents = $this->eventDispatcher->getDispatchedEvents();
            expect($dispatchedEvents)->toHaveCount(1);
            expect($dispatchedEvents[0])->toBeInstanceOf(ProjectCreatedEvent::class);
        });

        test('project can be loaded from event store', function (): void {
            // Create and save project
            $registerProjectCommand = ProjectTestFactory::createValidRegisterProjectCommand([
                'name' => 'Loadable Project'
            ]);
            $originalProject = ($this->registerHandler)($registerProjectCommand);

            // Load project from event store
            $loadedProject = $this->repository->load($originalProject->getId());

            expect($loadedProject)->not->toBeNull();
            expect($loadedProject->getId()->equals($originalProject->getId()))->toBeTrue();
            expect((string)$loadedProject->getName())->toBe('Loadable Project');
            expect($loadedProject->getOwnerId()->equals($originalProject->getOwnerId()))->toBeTrue();
        });

        test('multiple operations create event stream', function (): void {
            // Create project
            $registerProjectCommand = ProjectTestFactory::createValidRegisterProjectCommand([
                'name' => 'Event Stream Test'
            ]);
            $project = ($this->registerHandler)($registerProjectCommand);

            // Rename project
            $renameProjectCommand = RenameProjectCommand::fromPrimitives(
                (string)$project->getId(),
                'Renamed Event Stream Test'
            );
            ($this->renameHandler)($renameProjectCommand);

            // Add worker
            $addProjectWorkerCommand = AddProjectWorkerCommand::fromPrimitives(
                (string)$project->getId(),
                (string)ProjectTestFactory::createValidUuid(),
                'participant',
                (string)ProjectTestFactory::createValidUuid()
            );
            ($this->addWorkerHandler)($addProjectWorkerCommand);

            // Verify event stream
            $storedEvents = $this->eventStore->getEvents($project->getId());
            expect($storedEvents)->toHaveCount(3);
            expect($storedEvents[0])->toBeInstanceOf(ProjectCreatedEvent::class);
            expect($storedEvents[1])->toBeInstanceOf(ProjectRenamedEvent::class);
            expect($storedEvents[2])->toBeInstanceOf(ProjectWorkerAddedEvent::class);

            // Verify project can be reconstructed from events
            $reconstructedProject = $this->repository->load($project->getId());
            expect((string)$reconstructedProject->getName())->toBe('Renamed Event Stream Test');
            expect($reconstructedProject->getWorkers())->toHaveCount(1);
        });

        test('project state is correctly reconstructed from events', function (): void {
            // Create project
            $registerProjectCommand = ProjectTestFactory::createValidRegisterProjectCommand([
                'name' => 'Reconstruction Test'
            ]);
            $project = ($this->registerHandler)($registerProjectCommand);

            $uuid = ProjectTestFactory::createValidUuid();
            $userId2 = ProjectTestFactory::createValidUuid();
            $addedBy = ProjectTestFactory::createValidUuid();

            // Add workers
            ($this->addWorkerHandler)(AddProjectWorkerCommand::fromPrimitives(
                (string)$project->getId(),
                (string)$uuid,
                'participant',
                (string)$addedBy
            ));

            ($this->addWorkerHandler)(AddProjectWorkerCommand::fromPrimitives(
                (string)$project->getId(),
                (string)$userId2,
                'owner',
                (string)$addedBy
            ));

            // Rename project
            ($this->renameHandler)(RenameProjectCommand::fromPrimitives(
                (string)$project->getId(),
                'Final Name'
            ));

            // Clear in-memory state and reload from events
            $this->repository = new ProjectEventStoreRepository(
                $this->eventStore,
                $this->eventDispatcher,
                $this->snapshotStore,
                $this->snapshotFactory,
                $this->snapshotStrategy
            );

            $reconstructedProject = $this->repository->load($project->getId());

            // Verify state is correctly reconstructed
            expect((string)$reconstructedProject->getName())->toBe('Final Name');
            expect($reconstructedProject->getWorkers())->toHaveCount(2);
            expect($reconstructedProject->getId()->equals($project->getId()))->toBeTrue();
            expect($reconstructedProject->isDeleted())->toBeFalse();

            // Verify workers are correctly reconstructed
            $workers = $reconstructedProject->getWorkers();
            $userIds = array_map(fn($worker): string => (string)$worker->getUserId(), $workers);
            expect($userIds)->toContain((string)$uuid);
            expect($userIds)->toContain((string)$userId2);
        });
    });

    describe('Event Store Error Scenarios', function (): void {

        test('loading non-existent aggregate returns null', function (): void {
            $uuid = ProjectTestFactory::createValidUuid();

            $project = $this->repository->load($uuid);

            expect($project)->toBeNull();
        });

        test('checking existence of non-existent aggregate returns false', function (): void {
            $uuid = ProjectTestFactory::createValidUuid();

            $exists = $this->repository->exists($uuid);

            expect($exists)->toBeFalse();
        });
    });

    describe('Event Dispatcher Integration', function (): void {

        test('all domain events are dispatched after save', function (): void {
            // Create project with multiple operations
            $registerProjectCommand = ProjectTestFactory::createValidRegisterProjectCommand();
            $project = ($this->registerHandler)($registerProjectCommand);

            ($this->renameHandler)(RenameProjectCommand::fromPrimitives(
                (string)$project->getId(),
                'Dispatcher Test'
            ));

            ($this->addWorkerHandler)(AddProjectWorkerCommand::fromPrimitives(
                (string)$project->getId(),
                (string)ProjectTestFactory::createValidUuid(),
                'participant',
                (string)ProjectTestFactory::createValidUuid()
            ));

            // Verify all events were dispatched
            $dispatchedEvents = $this->eventDispatcher->getDispatchedEvents();
            expect($dispatchedEvents)->toHaveCount(3);

            $eventTypes = array_map(fn($event): string|false => $event::class, $dispatchedEvents);
            expect($eventTypes)->toContain(ProjectCreatedEvent::class);
            expect($eventTypes)->toContain(ProjectRenamedEvent::class);
            expect($eventTypes)->toContain(ProjectWorkerAddedEvent::class);
        });
    });
});
