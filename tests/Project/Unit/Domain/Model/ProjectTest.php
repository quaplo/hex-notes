<?php

declare(strict_types=1);

use App\Shared\ValueObject\Uuid;
use App\Project\Domain\ValueObject\ProjectRole;
use App\Project\Domain\Model\Project;
use App\Tests\Project\Helpers\ProjectEventAsserter;
use App\Tests\Project\Helpers\ProjectTestFactory;

describe('Project Domain Model', function (): void {

    test('project can be created with valid data', function (): void {
        $projectName = ProjectTestFactory::createProjectName('My Project');
        $uuid = ProjectTestFactory::createValidUuid();

        $project = Project::create($projectName, $uuid);

        expect((string)$project->getName())->toBe('My Project');
        expect($project->getOwnerId()->equals($uuid))->toBeTrue();
        expect($project->getCreatedAt())->toBeInstanceOf(DateTimeImmutable::class);
        expect($project->getId())->toBeInstanceOf(Uuid::class);
        expect($project->isDeleted())->toBeFalse();
        expect($project->getDeletedAt())->toBeNull();
        expect($project->getWorkers())->toBeEmpty();
    });

    test('project creation records ProjectCreatedEvent', function (): void {
        $projectName = ProjectTestFactory::createProjectName('Test Project');
        $uuid = ProjectTestFactory::createValidUuid();

        $project = Project::create($projectName, $uuid);
        $events = $project->getDomainEvents();

        ProjectEventAsserter::assertEventCount($events, 1);
        ProjectEventAsserter::assertProjectCreatedEvent($events[0], $project->getId(), $projectName, $uuid);
    });

    test('project can be renamed', function (): void {
        $project = ProjectTestFactory::createProject([
            'name' => ProjectTestFactory::createProjectName('Old Name')
        ]);
        $projectName = ProjectTestFactory::createProjectName('New Name');

        $renamedProject = $project->rename($projectName);

        expect((string)$renamedProject->getName())->toBe('New Name');
        expect($renamedProject->getId()->equals($project->getId()))->toBeTrue();
        expect($renamedProject->getOwnerId()->equals($project->getOwnerId()))->toBeTrue();
    });

    test('project rename records ProjectRenamedEvent', function (): void {
        $projectName = ProjectTestFactory::createProjectName('Old Name');
        $project = ProjectTestFactory::createProject(['name' => $projectName]);
        $newName = ProjectTestFactory::createProjectName('New Name');

        $renamedProject = $project->rename($newName);
        $events = $renamedProject->getDomainEvents();

        ProjectEventAsserter::assertEventCount($events, 1);
        ProjectEventAsserter::assertProjectRenamedEvent($events[0], $project->getId(), $projectName, $newName);
    });

    test('deleted project cannot be renamed', function (): void {
        $project = ProjectTestFactory::createProject([
            'deletedAt' => new DateTimeImmutable()
        ]);
        $projectName = ProjectTestFactory::createProjectName('New Name');

        expect(fn(): Project => $project->rename($projectName))
            ->toThrow(DomainException::class, 'Cannot rename deleted project');
    });

    test('project can be deleted', function (): void {
        $project = ProjectTestFactory::createProject();

        $deletedProject = $project->delete();

        expect($deletedProject->isDeleted())->toBeTrue();
        expect($deletedProject->getDeletedAt())->toBeInstanceOf(DateTimeImmutable::class);
        expect($deletedProject->getId()->equals($project->getId()))->toBeTrue();
    });

    test('project deletion records ProjectDeletedEvent', function (): void {
        $project = ProjectTestFactory::createProject();

        $deletedProject = $project->delete();
        $events = $deletedProject->getDomainEvents();

        ProjectEventAsserter::assertEventCount($events, 1);
        ProjectEventAsserter::assertProjectDeletedEvent($events[0], $project->getId());
    });

    test('already deleted project cannot be deleted again', function (): void {
        $project = ProjectTestFactory::createProject([
            'deletedAt' => new DateTimeImmutable()
        ]);

        expect(fn(): Project => $project->delete())
            ->toThrow(DomainException::class, 'Project is already deleted');
    });

    test('worker can be added to project', function (): void {
        $project = ProjectTestFactory::createProject();
        $projectWorker = ProjectTestFactory::createProjectWorker();

        $updatedProject = $project->addWorker($projectWorker);

        expect($updatedProject->getWorkers())->toHaveCount(1);
        expect($updatedProject->getWorkers()[0]->getUserId()->equals($projectWorker->getUserId()))->toBeTrue();
    });

    test('adding worker records ProjectWorkerAddedEvent', function (): void {
        $project = ProjectTestFactory::createProject();
        $projectWorker = ProjectTestFactory::createProjectWorker([
            'userId' => ProjectTestFactory::createValidUuid(),
            'role' => ProjectRole::PARTICIPANT
        ]);

        $updatedProject = $project->addWorker($projectWorker);
        $events = $updatedProject->getDomainEvents();

        ProjectEventAsserter::assertEventCount($events, 1);
        ProjectEventAsserter::assertProjectWorkerAddedEvent(
            $events[0],
            $project->getId(),
            $projectWorker->getUserId(),
            $projectWorker->getRole(),
            $projectWorker->getAddedBy()
        );
    });

    test('duplicate worker cannot be added', function (): void {
        $uuid = ProjectTestFactory::createValidUuid();
        $project = ProjectTestFactory::createProject();
        $projectWorker = ProjectTestFactory::createProjectWorker(['userId' => $uuid]);
        $worker2 = ProjectTestFactory::createProjectWorker(['userId' => $uuid]);

        $projectWithWorker = $project->addWorker($projectWorker);
        $finalProject = $projectWithWorker->addWorker($worker2);

        expect($finalProject->getWorkers())->toHaveCount(1);
    });

    test('worker cannot be added to deleted project', function (): void {
        $project = ProjectTestFactory::createProject([
            'deletedAt' => new DateTimeImmutable()
        ]);
        $projectWorker = ProjectTestFactory::createProjectWorker();

        expect(fn(): Project => $project->addWorker($projectWorker))
            ->toThrow(DomainException::class, 'Cannot add worker to deleted project');
    });

    test('worker can be removed from project', function (): void {
        $uuid = ProjectTestFactory::createValidUuid();
        $project = ProjectTestFactory::createProject();
        $projectWorker = ProjectTestFactory::createProjectWorker(['userId' => $uuid]);
        $projectWithWorker = $project->addWorker($projectWorker);

        $updatedProject = $projectWithWorker->removeWorkerByUserId($uuid);

        expect($updatedProject->getWorkers())->toBeEmpty();
    });

    test('removing worker records ProjectWorkerRemovedEvent', function (): void {
        $uuid = ProjectTestFactory::createValidUuid();
        $removedBy = ProjectTestFactory::createValidUuid();
        $project = ProjectTestFactory::createProject();
        $projectWorker = ProjectTestFactory::createProjectWorker(['userId' => $uuid]);
        $projectWithWorker = $project->addWorker($projectWorker);

        $updatedProject = $projectWithWorker->removeWorkerByUserId($uuid, $removedBy);
        $events = $updatedProject->getDomainEvents();

        ProjectEventAsserter::assertEventCount($events, 2);
        ProjectEventAsserter::assertProjectWorkerRemovedEvent(
            $events[1],
            $project->getId(),
            $uuid,
            $removedBy
        );
    });

    test('removing non-existent worker throws exception', function (): void {
        $project = ProjectTestFactory::createProject();
        $uuid = ProjectTestFactory::createValidUuid();

        expect(fn(): Project => $project->removeWorkerByUserId($uuid))
            ->toThrow(DomainException::class, 'Worker not found in project');
    });

    test('worker cannot be removed from deleted project', function (): void {
        $uuid = ProjectTestFactory::createValidUuid();
        $project = ProjectTestFactory::createProject([
            'deletedAt' => new DateTimeImmutable()
        ]);

        expect(fn(): Project => $project->removeWorkerByUserId($uuid))
            ->toThrow(DomainException::class, 'Cannot remove worker from deleted project');
    });
});
