<?php

declare(strict_types=1);

use App\Project\Application\Command\RegisterProjectHandler;
use App\Project\Application\Command\RenameProjectHandler;
use App\Project\Application\Command\DeleteProjectHandler;
use App\Project\Application\Command\AddProjectWorkerHandler;
use App\Project\Application\Command\RemoveProjectWorkerHandler;
use App\Project\Domain\Event\ProjectCreatedEvent;
use App\Project\Domain\Event\ProjectRenamedEvent;
use App\Project\Domain\Event\ProjectDeletedEvent;
use App\Project\Domain\Event\ProjectWorkerAddedEvent;
use App\Project\Domain\Event\ProjectWorkerRemovedEvent;
use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\ValueObject\ProjectRole;
use App\Tests\Project\Doubles\InMemoryProjectRepository;
use App\Tests\Project\Helpers\ProjectTestFactory;
use App\Tests\Project\Helpers\ProjectEventAsserter;

describe('Project Integration Tests', function () {
    
    beforeEach(function () {
        $this->repository = new InMemoryProjectRepository();
        $this->registerHandler = new RegisterProjectHandler($this->repository);
        $this->renameHandler = new RenameProjectHandler($this->repository);
        $this->deleteHandler = new DeleteProjectHandler($this->repository);
        $this->addWorkerHandler = new AddProjectWorkerHandler($this->repository);
        $this->removeWorkerHandler = new RemoveProjectWorkerHandler($this->repository);
    });

    describe('Complete Project Lifecycle', function () {
        
        test('project can be created, renamed, and deleted', function () {
            // Create project
            $registerCommand = ProjectTestFactory::createValidRegisterProjectCommand([
                'name' => 'Initial Project'
            ]);
            $project = ($this->registerHandler)($registerCommand);
            
            expect((string)$project->getName())->toBe('Initial Project');
            expect($project->isDeleted())->toBeFalse();
            expect($this->repository->count())->toBe(1);

            // Rename project
            $renameCommand = \App\Project\Application\Command\RenameProjectCommand::fromPrimitives(
                (string)$project->getId(),
                'Renamed Project'
            );
            $renamedProject = ($this->renameHandler)($renameCommand);
            
            expect((string)$renamedProject->getName())->toBe('Renamed Project');
            expect($renamedProject->getId()->equals($project->getId()))->toBeTrue();

            // Delete project
            $deleteCommand = \App\Project\Application\Command\DeleteProjectCommand::fromPrimitives(
                (string)$project->getId()
            );
            $deletedProject = ($this->deleteHandler)($deleteCommand);
            
            expect($deletedProject->isDeleted())->toBeTrue();
            expect($deletedProject->getId()->equals($project->getId()))->toBeTrue();
        });

        test('project worker management workflow', function () {
            // Create project
            $registerCommand = ProjectTestFactory::createValidRegisterProjectCommand([
                'name' => 'Team Project'
            ]);
            $project = ($this->registerHandler)($registerCommand);
            
            $userId1 = ProjectTestFactory::createValidUuid();
            $userId2 = ProjectTestFactory::createValidUuid();
            $addedBy = ProjectTestFactory::createValidUuid();

            // Add first worker
            $addWorkerCommand1 = \App\Project\Application\Command\AddProjectWorkerCommand::fromPrimitives(
                (string)$project->getId(),
                (string)$userId1,
                'participant',
                (string)$addedBy
            );
            $projectWithWorker1 = ($this->addWorkerHandler)($addWorkerCommand1);
            
            expect($projectWithWorker1->getWorkers())->toHaveCount(1);

            // Add second worker
            $addWorkerCommand2 = \App\Project\Application\Command\AddProjectWorkerCommand::fromPrimitives(
                (string)$project->getId(),
                (string)$userId2,
                'owner',
                (string)$addedBy
            );
            $projectWithWorker2 = ($this->addWorkerHandler)($addWorkerCommand2);
            
            expect($projectWithWorker2->getWorkers())->toHaveCount(2);

            // Remove first worker
            $removeWorkerCommand = \App\Project\Application\Command\RemoveProjectWorkerCommand::fromPrimitives(
                (string)$project->getId(),
                (string)$userId1,
                (string)$addedBy
            );
            $projectWithRemovedWorker = ($this->removeWorkerHandler)($removeWorkerCommand);
            
            expect($projectWithRemovedWorker->getWorkers())->toHaveCount(1);
            
            // Verify remaining worker is the second one
            $workers = $projectWithRemovedWorker->getWorkers();
            expect($workers)->toHaveCount(1);
            $remainingWorker = reset($workers); // Get first element regardless of key
            expect($remainingWorker->getUserId()->equals($userId2))->toBeTrue();
            expect((string)$remainingWorker->getRole())->toBe('owner');
        });

    });

    describe('Event Sourcing Integration', function () {
        
        test('all operations record appropriate domain events', function () {
            // Register project
            $registerCommand = ProjectTestFactory::createValidRegisterProjectCommand([
                'name' => 'Event Test Project'
            ]);
            $project = ($this->registerHandler)($registerCommand);
            
            $events = $this->repository->getEventsForProject($project->getId());
            ProjectEventAsserter::assertEventCount($events, 1);
            ProjectEventAsserter::assertContainsEventType($events, ProjectCreatedEvent::class);

            // Rename project
            $renameCommand = \App\Project\Application\Command\RenameProjectCommand::fromPrimitives(
                (string)$project->getId(),
                'Renamed Event Project'
            );
            ($this->renameHandler)($renameCommand);
            
            $events = $this->repository->getEventsForProject($project->getId());
            ProjectEventAsserter::assertEventCount($events, 2);
            ProjectEventAsserter::assertContainsEventType($events, ProjectRenamedEvent::class);

            // Add worker
            $addWorkerCommand = \App\Project\Application\Command\AddProjectWorkerCommand::fromPrimitives(
                (string)$project->getId(),
                (string)ProjectTestFactory::createValidUuid(),
                'participant',
                (string)ProjectTestFactory::createValidUuid()
            );
            ($this->addWorkerHandler)($addWorkerCommand);
            
            $events = $this->repository->getEventsForProject($project->getId());
            ProjectEventAsserter::assertEventCount($events, 3);
            ProjectEventAsserter::assertContainsEventType($events, ProjectWorkerAddedEvent::class);

            // Delete project
            $deleteCommand = \App\Project\Application\Command\DeleteProjectCommand::fromPrimitives(
                (string)$project->getId()
            );
            ($this->deleteHandler)($deleteCommand);
            
            $events = $this->repository->getEventsForProject($project->getId());
            ProjectEventAsserter::assertEventCount($events, 4);
            ProjectEventAsserter::assertContainsEventType($events, ProjectDeletedEvent::class);
        });

    });

    describe('Error Scenarios', function () {
        
        test('operations on non-existent project throw ProjectNotFoundException', function () {
            $nonExistentId = ProjectTestFactory::createValidUuid();

            // Rename non-existent project
            expect(function () use ($nonExistentId) {
                $renameCommand = \App\Project\Application\Command\RenameProjectCommand::fromPrimitives(
                    (string)$nonExistentId,
                    'New Name'
                );
                ($this->renameHandler)($renameCommand);
            })->toThrow(ProjectNotFoundException::class);

            // Delete non-existent project
            expect(function () use ($nonExistentId) {
                $deleteCommand = \App\Project\Application\Command\DeleteProjectCommand::fromPrimitives(
                    (string)$nonExistentId
                );
                ($this->deleteHandler)($deleteCommand);
            })->toThrow(ProjectNotFoundException::class);

            // Add worker to non-existent project
            expect(function () use ($nonExistentId) {
                $addWorkerCommand = \App\Project\Application\Command\AddProjectWorkerCommand::fromPrimitives(
                    (string)$nonExistentId,
                    (string)ProjectTestFactory::createValidUuid(),
                    'participant',
                    (string)ProjectTestFactory::createValidUuid()
                );
                ($this->addWorkerHandler)($addWorkerCommand);
            })->toThrow(ProjectNotFoundException::class);

            // Remove worker from non-existent project
            expect(function () use ($nonExistentId) {
                $removeWorkerCommand = \App\Project\Application\Command\RemoveProjectWorkerCommand::fromPrimitives(
                    (string)$nonExistentId,
                    (string)ProjectTestFactory::createValidUuid()
                );
                ($this->removeWorkerHandler)($removeWorkerCommand);
            })->toThrow(ProjectNotFoundException::class);
        });

        test('operations on deleted project throw domain exceptions', function () {
            // Create and delete project
            $registerCommand = ProjectTestFactory::createValidRegisterProjectCommand([
                'name' => 'To Be Deleted'
            ]);
            $project = ($this->registerHandler)($registerCommand);
            
            $deleteCommand = \App\Project\Application\Command\DeleteProjectCommand::fromPrimitives(
                (string)$project->getId()
            );
            ($this->deleteHandler)($deleteCommand);

            // Try to rename deleted project
            expect(function () use ($project) {
                $renameCommand = \App\Project\Application\Command\RenameProjectCommand::fromPrimitives(
                    (string)$project->getId(),
                    'New Name'
                );
                ($this->renameHandler)($renameCommand);
            })->toThrow(DomainException::class, 'Cannot rename deleted project');

            // Try to add worker to deleted project
            expect(function () use ($project) {
                $addWorkerCommand = \App\Project\Application\Command\AddProjectWorkerCommand::fromPrimitives(
                    (string)$project->getId(),
                    (string)ProjectTestFactory::createValidUuid(),
                    'participant',
                    (string)ProjectTestFactory::createValidUuid()
                );
                ($this->addWorkerHandler)($addWorkerCommand);
            })->toThrow(DomainException::class, 'Cannot add worker to deleted project');
        });

        test('duplicate worker cannot be added', function () {
            // Create project
            $registerCommand = ProjectTestFactory::createValidRegisterProjectCommand();
            $project = ($this->registerHandler)($registerCommand);
            
            $userId = ProjectTestFactory::createValidUuid();
            $addedBy = ProjectTestFactory::createValidUuid();

            // Add worker first time
            $addWorkerCommand1 = \App\Project\Application\Command\AddProjectWorkerCommand::fromPrimitives(
                (string)$project->getId(),
                (string)$userId,
                'participant',
                (string)$addedBy
            );
            $projectWithWorker = ($this->addWorkerHandler)($addWorkerCommand1);
            expect($projectWithWorker->getWorkers())->toHaveCount(1);

            // Try to add same worker again
            $addWorkerCommand2 = \App\Project\Application\Command\AddProjectWorkerCommand::fromPrimitives(
                (string)$project->getId(),
                (string)$userId,
                'owner',
                (string)$addedBy
            );
            $finalProject = ($this->addWorkerHandler)($addWorkerCommand2);
            
            // Should still have only 1 worker (duplicate ignored)
            expect($finalProject->getWorkers())->toHaveCount(1);
        });

        test('removing non-existent worker throws exception', function () {
            // Create project
            $registerCommand = ProjectTestFactory::createValidRegisterProjectCommand();
            $project = ($this->registerHandler)($registerCommand);
            
            $nonExistentUserId = ProjectTestFactory::createValidUuid();

            expect(function () use ($project, $nonExistentUserId) {
                $removeWorkerCommand = \App\Project\Application\Command\RemoveProjectWorkerCommand::fromPrimitives(
                    (string)$project->getId(),
                    (string)$nonExistentUserId
                );
                ($this->removeWorkerHandler)($removeWorkerCommand);
            })->toThrow(DomainException::class, 'Worker not found in project');
        });

    });

    describe('Concurrency and State Consistency', function () {
        
        test('multiple operations maintain consistent state', function () {
            // Create project
            $registerCommand = ProjectTestFactory::createValidRegisterProjectCommand([
                'name' => 'Concurrency Test'
            ]);
            $project = ($this->registerHandler)($registerCommand);
            
            // Simulate concurrent operations
            $userId1 = ProjectTestFactory::createValidUuid();
            $userId2 = ProjectTestFactory::createValidUuid();
            $userId3 = ProjectTestFactory::createValidUuid();
            $addedBy = ProjectTestFactory::createValidUuid();

            // Add multiple workers
            ($this->addWorkerHandler)(\App\Project\Application\Command\AddProjectWorkerCommand::fromPrimitives(
                (string)$project->getId(), (string)$userId1, 'participant', (string)$addedBy
            ));
            
            ($this->addWorkerHandler)(\App\Project\Application\Command\AddProjectWorkerCommand::fromPrimitives(
                (string)$project->getId(), (string)$userId2, 'owner', (string)$addedBy
            ));
            
            ($this->addWorkerHandler)(\App\Project\Application\Command\AddProjectWorkerCommand::fromPrimitives(
                (string)$project->getId(), (string)$userId3, 'participant', (string)$addedBy
            ));

            // Rename project
            ($this->renameHandler)(\App\Project\Application\Command\RenameProjectCommand::fromPrimitives(
                (string)$project->getId(), 'Renamed Concurrency Test'
            ));

            // Remove one worker
            ($this->removeWorkerHandler)(\App\Project\Application\Command\RemoveProjectWorkerCommand::fromPrimitives(
                (string)$project->getId(), (string)$userId2, (string)$addedBy
            ));

            // Verify final state
            $finalProject = $this->repository->getProject($project->getId());
            expect((string)$finalProject->getName())->toBe('Renamed Concurrency Test');
            expect($finalProject->getWorkers())->toHaveCount(2);
            expect($finalProject->isDeleted())->toBeFalse();

            // Verify events were recorded correctly
            $events = $this->repository->getEventsForProject($project->getId());
            expect(count($events))->toBe(6); // Created + 3 workers added + renamed + 1 worker removed
        });

    });

});