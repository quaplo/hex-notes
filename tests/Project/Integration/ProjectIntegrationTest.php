<?php

declare(strict_types=1);

use App\Project\Application\Command\Delete\DeleteProjectCommand;
use App\Project\Application\Command\Delete\DeleteProjectHandler;
use App\Project\Application\Command\Register\RegisterProjectHandler;
use App\Project\Application\Command\Rename\RenameProjectCommand;
use App\Project\Application\Command\Rename\RenameProjectHandler;
use App\Project\Application\Command\Worker\AddProjectWorkerCommand;
use App\Project\Application\Command\Worker\AddProjectWorkerHandler;
use App\Project\Application\Command\Worker\RemoveProjectWorkerCommand;
use App\Project\Application\Command\Worker\RemoveProjectWorkerHandler;
use App\Project\Domain\Event\ProjectCreatedEvent;
use App\Project\Domain\Event\ProjectDeletedEvent;
use App\Project\Domain\Event\ProjectRenamedEvent;
use App\Project\Domain\Event\ProjectWorkerAddedEvent;
use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Tests\Project\Doubles\InMemoryProjectRepository;
use App\Tests\Project\Helpers\ProjectEventAsserter;
use App\Tests\Project\Helpers\ProjectTestFactory;

describe('Project Integration Tests', function (): void {
    beforeEach(function (): void {
        $this->repository = new InMemoryProjectRepository();
        $this->registerHandler = new RegisterProjectHandler($this->repository);
        $this->renameHandler = new RenameProjectHandler($this->repository);
        $this->deleteHandler = new DeleteProjectHandler($this->repository);
        $this->addWorkerHandler = new AddProjectWorkerHandler($this->repository);
        $this->removeWorkerHandler = new RemoveProjectWorkerHandler($this->repository);
    });

    describe('Complete Project Lifecycle', function (): void {
        test('project can be created, renamed, and deleted', function (): void {
            // Create project
            $registerProjectCommand = ProjectTestFactory::createValidRegisterProjectCommand([
                'name' => 'Initial Project',
            ]);
            $project = ($this->registerHandler)($registerProjectCommand);

            expect((string) $project->getName())->toBe('Initial Project');
            expect($project->isDeleted())->toBeFalse();
            expect($this->repository->count())->toBe(1);

            // Rename project
            $renameProjectCommand = RenameProjectCommand::fromPrimitives(
                (string) $project->getId(),
                'Renamed Project'
            );
            $renamedProject = ($this->renameHandler)($renameProjectCommand);

            expect((string) $renamedProject->getName())->toBe('Renamed Project');
            expect($renamedProject->getId()->equals($project->getId()))->toBeTrue();

            // Delete project
            $deleteProjectCommand = DeleteProjectCommand::fromPrimitives(
                (string) $project->getId(),
                (string) $project->getOwnerId()
            );
            $deletedProject = ($this->deleteHandler)($deleteProjectCommand);

            expect($deletedProject->isDeleted())->toBeTrue();
            expect($deletedProject->getId()->equals($project->getId()))->toBeTrue();
        });

        test('project worker management workflow', function (): void {
            // Create project
            $registerProjectCommand = ProjectTestFactory::createValidRegisterProjectCommand([
                'name' => 'Team Project',
            ]);
            $project = ($this->registerHandler)($registerProjectCommand);

            $uuid = ProjectTestFactory::createValidUuid();
            $userId2 = ProjectTestFactory::createValidUuid();
            $addedBy = ProjectTestFactory::createValidUuid();

            // Add first worker
            $addProjectWorkerCommand = AddProjectWorkerCommand::fromPrimitives(
                (string) $project->getId(),
                (string) $uuid,
                'participant',
                (string) $addedBy
            );
            $projectWithWorker1 = ($this->addWorkerHandler)($addProjectWorkerCommand);

            expect($projectWithWorker1->getWorkers())->toHaveCount(1);

            // Add second worker
            $addWorkerCommand2 = AddProjectWorkerCommand::fromPrimitives(
                (string) $project->getId(),
                (string) $userId2,
                'owner',
                (string) $addedBy
            );
            $projectWithWorker2 = ($this->addWorkerHandler)($addWorkerCommand2);

            expect($projectWithWorker2->getWorkers())->toHaveCount(2);

            // Remove first worker
            $removeProjectWorkerCommand = RemoveProjectWorkerCommand::fromPrimitives(
                (string) $project->getId(),
                (string) $uuid,
                (string) $addedBy
            );
            $projectWithRemovedWorker = ($this->removeWorkerHandler)($removeProjectWorkerCommand);

            expect($projectWithRemovedWorker->getWorkers())->toHaveCount(1);

            // Verify remaining worker is the second one
            $workers = $projectWithRemovedWorker->getWorkers();
            expect($workers)->toHaveCount(1);
            $remainingWorker = reset($workers); // Get first element regardless of key
            expect($remainingWorker->getUserId()->equals($userId2))->toBeTrue();
            expect($remainingWorker->getRole()->toString())->toBe('owner');
        });
    });

    describe('Event Sourcing Integration', function (): void {
        test('all operations record appropriate domain events', function (): void {
            // Register project
            $registerProjectCommand = ProjectTestFactory::createValidRegisterProjectCommand([
                'name' => 'Event Test Project',
            ]);
            $project = ($this->registerHandler)($registerProjectCommand);

            $events = $this->repository->getEventsForProject($project->getId());
            ProjectEventAsserter::assertEventCount($events, 1);
            ProjectEventAsserter::assertContainsEventType($events, ProjectCreatedEvent::class);

            // Rename project
            $renameProjectCommand = RenameProjectCommand::fromPrimitives(
                (string) $project->getId(),
                'Renamed Event Project'
            );
            ($this->renameHandler)($renameProjectCommand);

            $events = $this->repository->getEventsForProject($project->getId());
            ProjectEventAsserter::assertEventCount($events, 2);
            ProjectEventAsserter::assertContainsEventType($events, ProjectRenamedEvent::class);

            // Add worker
            $addProjectWorkerCommand = AddProjectWorkerCommand::fromPrimitives(
                (string) $project->getId(),
                (string) ProjectTestFactory::createValidUuid(),
                'participant',
                (string) ProjectTestFactory::createValidUuid()
            );
            ($this->addWorkerHandler)($addProjectWorkerCommand);

            $events = $this->repository->getEventsForProject($project->getId());
            ProjectEventAsserter::assertEventCount($events, 3);
            ProjectEventAsserter::assertContainsEventType($events, ProjectWorkerAddedEvent::class);

            // Delete project
            $deleteProjectCommand = DeleteProjectCommand::fromPrimitives(
                (string) $project->getId(),
                (string) $project->getOwnerId()
            );
            ($this->deleteHandler)($deleteProjectCommand);

            $events = $this->repository->getEventsForProject($project->getId());
            ProjectEventAsserter::assertEventCount($events, 4);
            ProjectEventAsserter::assertContainsEventType($events, ProjectDeletedEvent::class);
        });
    });

    describe('Error Scenarios', function (): void {
        test('operations on non-existent project throw ProjectNotFoundException', function (): void {
            $uuid = ProjectTestFactory::createValidUuid();

            // Rename non-existent project
            expect(function () use ($uuid): void {
                $renameProjectCommand = RenameProjectCommand::fromPrimitives(
                    (string) $uuid,
                    'New Name'
                );
                ($this->renameHandler)($renameProjectCommand);
            })->toThrow(ProjectNotFoundException::class);

            // Delete non-existent project
            expect(function () use ($uuid): void {
                $deleteProjectCommand = DeleteProjectCommand::fromPrimitives(
                    (string) $uuid,
                    (string) ProjectTestFactory::createValidUuid()
                );
                ($this->deleteHandler)($deleteProjectCommand);
            })->toThrow(ProjectNotFoundException::class);

            // Add worker to non-existent project
            expect(function () use ($uuid): void {
                $addProjectWorkerCommand = AddProjectWorkerCommand::fromPrimitives(
                    (string) $uuid,
                    (string) ProjectTestFactory::createValidUuid(),
                    'participant',
                    (string) ProjectTestFactory::createValidUuid()
                );
                ($this->addWorkerHandler)($addProjectWorkerCommand);
            })->toThrow(ProjectNotFoundException::class);

            // Remove worker from non-existent project
            expect(function () use ($uuid): void {
                $removeProjectWorkerCommand = RemoveProjectWorkerCommand::fromPrimitives(
                    (string) $uuid,
                    (string) ProjectTestFactory::createValidUuid()
                );
                ($this->removeWorkerHandler)($removeProjectWorkerCommand);
            })->toThrow(ProjectNotFoundException::class);
        });

        test('operations on deleted project throw domain exceptions', function (): void {
            // Create and delete project
            $registerProjectCommand = ProjectTestFactory::createValidRegisterProjectCommand([
                'name' => 'To Be Deleted',
            ]);
            $project = ($this->registerHandler)($registerProjectCommand);

            $deleteProjectCommand = DeleteProjectCommand::fromPrimitives(
                (string) $project->getId(),
                (string) $project->getOwnerId()
            );
            ($this->deleteHandler)($deleteProjectCommand);

            // Try to rename deleted project
            expect(function () use ($project): void {
                $renameProjectCommand = RenameProjectCommand::fromPrimitives(
                    (string) $project->getId(),
                    'New Name'
                );
                ($this->renameHandler)($renameProjectCommand);
            })->toThrow(DomainException::class, 'Cannot rename deleted project');

            // Try to add worker to deleted project
            expect(function () use ($project): void {
                $addProjectWorkerCommand = AddProjectWorkerCommand::fromPrimitives(
                    (string) $project->getId(),
                    (string) ProjectTestFactory::createValidUuid(),
                    'participant',
                    (string) ProjectTestFactory::createValidUuid()
                );
                ($this->addWorkerHandler)($addProjectWorkerCommand);
            })->toThrow(DomainException::class, 'Cannot add worker to deleted project');
        });

        test('duplicate worker cannot be added', function (): void {
            // Create project
            $registerProjectCommand = ProjectTestFactory::createValidRegisterProjectCommand();
            $project = ($this->registerHandler)($registerProjectCommand);

            $uuid = ProjectTestFactory::createValidUuid();
            $addedBy = ProjectTestFactory::createValidUuid();

            // Add worker first time
            $addProjectWorkerCommand = AddProjectWorkerCommand::fromPrimitives(
                (string) $project->getId(),
                (string) $uuid,
                'participant',
                (string) $addedBy
            );
            $projectWithWorker = ($this->addWorkerHandler)($addProjectWorkerCommand);
            expect($projectWithWorker->getWorkers())->toHaveCount(1);

            // Try to add same worker again
            $addWorkerCommand2 = AddProjectWorkerCommand::fromPrimitives(
                (string) $project->getId(),
                (string) $uuid,
                'owner',
                (string) $addedBy
            );
            $finalProject = ($this->addWorkerHandler)($addWorkerCommand2);

            // Should still have only 1 worker (duplicate ignored)
            expect($finalProject->getWorkers())->toHaveCount(1);
        });

        test('removing non-existent worker throws exception', function (): void {
            // Create project
            $registerProjectCommand = ProjectTestFactory::createValidRegisterProjectCommand();
            $project = ($this->registerHandler)($registerProjectCommand);

            $uuid = ProjectTestFactory::createValidUuid();

            expect(function () use ($project, $uuid): void {
                $removeProjectWorkerCommand = RemoveProjectWorkerCommand::fromPrimitives(
                    (string) $project->getId(),
                    (string) $uuid
                );
                ($this->removeWorkerHandler)($removeProjectWorkerCommand);
            })->toThrow(DomainException::class, 'Worker not found in project');
        });
    });

    describe('Concurrency and State Consistency', function (): void {
        test('multiple operations maintain consistent state', function (): void {
            // Create project
            $registerProjectCommand = ProjectTestFactory::createValidRegisterProjectCommand([
                'name' => 'Concurrency Test',
            ]);
            $project = ($this->registerHandler)($registerProjectCommand);

            // Simulate concurrent operations
            $uuid = ProjectTestFactory::createValidUuid();
            $userId2 = ProjectTestFactory::createValidUuid();
            $userId3 = ProjectTestFactory::createValidUuid();
            $addedBy = ProjectTestFactory::createValidUuid();

            // Add multiple workers
            ($this->addWorkerHandler)(AddProjectWorkerCommand::fromPrimitives(
                (string) $project->getId(),
                (string) $uuid,
                'participant',
                (string) $addedBy
            ));

            ($this->addWorkerHandler)(AddProjectWorkerCommand::fromPrimitives(
                (string) $project->getId(),
                (string) $userId2,
                'owner',
                (string) $addedBy
            ));

            ($this->addWorkerHandler)(AddProjectWorkerCommand::fromPrimitives(
                (string) $project->getId(),
                (string) $userId3,
                'participant',
                (string) $addedBy
            ));

            // Rename project
            ($this->renameHandler)(RenameProjectCommand::fromPrimitives(
                (string) $project->getId(),
                'Renamed Concurrency Test'
            ));

            // Remove one worker
            ($this->removeWorkerHandler)(RemoveProjectWorkerCommand::fromPrimitives(
                (string) $project->getId(),
                (string) $userId2,
                (string) $addedBy
            ));

            // Verify final state
            $finalProject = $this->repository->getProject($project->getId());
            expect((string) $finalProject->getName())->toBe('Renamed Concurrency Test');
            expect($finalProject->getWorkers())->toHaveCount(2);
            expect($finalProject->isDeleted())->toBeFalse();

            // Verify events were recorded correctly
            $events = $this->repository->getEventsForProject($project->getId());
            expect(count($events))->toBe(6); // Created + 3 workers added + renamed + 1 worker removed
        });
    });
});
