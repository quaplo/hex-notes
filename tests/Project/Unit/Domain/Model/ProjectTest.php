<?php

declare(strict_types=1);

use App\Project\Domain\Event\ProjectCreatedEvent;
use App\Project\Domain\Event\ProjectDeletedEvent;
use App\Project\Domain\Event\ProjectRenamedEvent;
use App\Project\Domain\Event\ProjectWorkerAddedEvent;
use App\Project\Domain\Event\ProjectWorkerRemovedEvent;
use App\Project\Domain\Model\Project;
use App\Project\Domain\ValueObject\ProjectName;
use App\Tests\Project\Helpers\ProjectEventAsserter;
use App\Tests\Project\Helpers\ProjectTestFactory;

describe('Project Domain Model', function () {
    
    test('project can be created with valid data', function () {
        $name = ProjectTestFactory::createProjectName('My Project');
        $ownerId = ProjectTestFactory::createValidUuid();

        $project = Project::create($name, $ownerId);

        expect((string)$project->getName())->toBe('My Project');
        expect($project->getOwnerId()->equals($ownerId))->toBeTrue();
        expect($project->getCreatedAt())->toBeInstanceOf(DateTimeImmutable::class);
        expect($project->getId())->toBeInstanceOf(\App\Shared\ValueObject\Uuid::class);
        expect($project->isDeleted())->toBeFalse();
        expect($project->getDeletedAt())->toBeNull();
        expect($project->getWorkers())->toBeEmpty();
    });

    test('project creation records ProjectCreatedEvent', function () {
        $name = ProjectTestFactory::createProjectName('Test Project');
        $ownerId = ProjectTestFactory::createValidUuid();

        $project = Project::create($name, $ownerId);
        $events = $project->getDomainEvents();

        ProjectEventAsserter::assertEventCount($events, 1);
        ProjectEventAsserter::assertProjectCreatedEvent($events[0], $project->getId(), $name, $ownerId);
    });

    test('project can be renamed', function () {
        $project = ProjectTestFactory::createProject([
            'name' => ProjectTestFactory::createProjectName('Old Name')
        ]);
        $newName = ProjectTestFactory::createProjectName('New Name');

        $renamedProject = $project->rename($newName);

        expect((string)$renamedProject->getName())->toBe('New Name');
        expect($renamedProject->getId()->equals($project->getId()))->toBeTrue();
        expect($renamedProject->getOwnerId()->equals($project->getOwnerId()))->toBeTrue();
    });

    test('project rename records ProjectRenamedEvent', function () {
        $oldName = ProjectTestFactory::createProjectName('Old Name');
        $project = ProjectTestFactory::createProject(['name' => $oldName]);
        $newName = ProjectTestFactory::createProjectName('New Name');

        $renamedProject = $project->rename($newName);
        $events = $renamedProject->getDomainEvents();

        ProjectEventAsserter::assertEventCount($events, 1);
        ProjectEventAsserter::assertProjectRenamedEvent($events[0], $project->getId(), $oldName, $newName);
    });

    test('deleted project cannot be renamed', function () {
        $project = ProjectTestFactory::createProject([
            'deletedAt' => new DateTimeImmutable()
        ]);
        $newName = ProjectTestFactory::createProjectName('New Name');

        expect(fn() => $project->rename($newName))
            ->toThrow(DomainException::class, 'Cannot rename deleted project');
    });

    test('project can be deleted', function () {
        $project = ProjectTestFactory::createProject();

        $deletedProject = $project->delete();

        expect($deletedProject->isDeleted())->toBeTrue();
        expect($deletedProject->getDeletedAt())->toBeInstanceOf(DateTimeImmutable::class);
        expect($deletedProject->getId()->equals($project->getId()))->toBeTrue();
    });

    test('project deletion records ProjectDeletedEvent', function () {
        $project = ProjectTestFactory::createProject();

        $deletedProject = $project->delete();
        $events = $deletedProject->getDomainEvents();

        ProjectEventAsserter::assertEventCount($events, 1);
        ProjectEventAsserter::assertProjectDeletedEvent($events[0], $project->getId());
    });

    test('already deleted project cannot be deleted again', function () {
        $project = ProjectTestFactory::createProject([
            'deletedAt' => new DateTimeImmutable()
        ]);

        expect(fn() => $project->delete())
            ->toThrow(DomainException::class, 'Project is already deleted');
    });

    test('worker can be added to project', function () {
        $project = ProjectTestFactory::createProject();
        $worker = ProjectTestFactory::createProjectWorker();

        $updatedProject = $project->addWorker($worker);

        expect($updatedProject->getWorkers())->toHaveCount(1);
        expect($updatedProject->getWorkers()[0]->getUserId()->equals($worker->getUserId()))->toBeTrue();
    });

    test('adding worker records ProjectWorkerAddedEvent', function () {
        $project = ProjectTestFactory::createProject();
        $worker = ProjectTestFactory::createProjectWorker([
            'userId' => ProjectTestFactory::createValidUuid(),
            'role' => \App\Project\Domain\ValueObject\ProjectRole::participant()
        ]);

        $updatedProject = $project->addWorker($worker);
        $events = $updatedProject->getDomainEvents();

        ProjectEventAsserter::assertEventCount($events, 1);
        ProjectEventAsserter::assertProjectWorkerAddedEvent(
            $events[0],
            $project->getId(),
            $worker->getUserId(),
            $worker->getRole(),
            $worker->getAddedBy()
        );
    });

    test('duplicate worker cannot be added', function () {
        $userId = ProjectTestFactory::createValidUuid();
        $project = ProjectTestFactory::createProject();
        $worker1 = ProjectTestFactory::createProjectWorker(['userId' => $userId]);
        $worker2 = ProjectTestFactory::createProjectWorker(['userId' => $userId]);

        $projectWithWorker = $project->addWorker($worker1);
        $finalProject = $projectWithWorker->addWorker($worker2);

        expect($finalProject->getWorkers())->toHaveCount(1);
    });

    test('worker cannot be added to deleted project', function () {
        $project = ProjectTestFactory::createProject([
            'deletedAt' => new DateTimeImmutable()
        ]);
        $worker = ProjectTestFactory::createProjectWorker();

        expect(fn() => $project->addWorker($worker))
            ->toThrow(DomainException::class, 'Cannot add worker to deleted project');
    });

    test('worker can be removed from project', function () {
        $userId = ProjectTestFactory::createValidUuid();
        $project = ProjectTestFactory::createProject();
        $worker = ProjectTestFactory::createProjectWorker(['userId' => $userId]);
        $projectWithWorker = $project->addWorker($worker);

        $updatedProject = $projectWithWorker->removeWorkerByUserId($userId);

        expect($updatedProject->getWorkers())->toBeEmpty();
    });

    test('removing worker records ProjectWorkerRemovedEvent', function () {
        $userId = ProjectTestFactory::createValidUuid();
        $removedBy = ProjectTestFactory::createValidUuid();
        $project = ProjectTestFactory::createProject();
        $worker = ProjectTestFactory::createProjectWorker(['userId' => $userId]);
        $projectWithWorker = $project->addWorker($worker);

        $updatedProject = $projectWithWorker->removeWorkerByUserId($userId, $removedBy);
        $events = $updatedProject->getDomainEvents();

        ProjectEventAsserter::assertEventCount($events, 2);
        ProjectEventAsserter::assertProjectWorkerRemovedEvent(
            $events[1],
            $project->getId(),
            $userId,
            $removedBy
        );
    });

    test('removing non-existent worker throws exception', function () {
        $project = ProjectTestFactory::createProject();
        $nonExistentUserId = ProjectTestFactory::createValidUuid();

        expect(fn() => $project->removeWorkerByUserId($nonExistentUserId))
            ->toThrow(DomainException::class, 'Worker not found in project');
    });

    test('worker cannot be removed from deleted project', function () {
        $userId = ProjectTestFactory::createValidUuid();
        $project = ProjectTestFactory::createProject([
            'deletedAt' => new DateTimeImmutable()
        ]);

        expect(fn() => $project->removeWorkerByUserId($userId))
            ->toThrow(DomainException::class, 'Cannot remove worker from deleted project');
    });

});