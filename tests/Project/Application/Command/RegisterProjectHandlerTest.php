<?php

declare(strict_types=1);

use App\Project\Application\Command\Register\RegisterProjectHandler;
use App\Project\Domain\Model\Project;
use App\Tests\Project\Doubles\InMemoryProjectRepository;
use App\Tests\Project\Helpers\ProjectEventAsserter;
use App\Tests\Project\Helpers\ProjectTestFactory;

describe('RegisterProjectHandler', function (): void {

    test('register project handler creates new project', function (): void {
        $repository = new InMemoryProjectRepository();
        $handler = new RegisterProjectHandler($repository);
        $registerProjectCommand = ProjectTestFactory::createValidRegisterProjectCommand([
            'name' => 'New Project'
        ]);

        $project = $handler($registerProjectCommand);

        expect($project)->toBeInstanceOf(Project::class);
        expect((string)$project->getName())->toBe('New Project');
        expect($project->getOwnerId()->equals($registerProjectCommand->ownerId))->toBeTrue();
        expect($project->isDeleted())->toBeFalse();
    });

    test('register project handler saves project via repository', function (): void {
        $repository = new InMemoryProjectRepository();
        $handler = new RegisterProjectHandler($repository);
        $registerProjectCommand = ProjectTestFactory::createValidRegisterProjectCommand();

        $project = $handler($registerProjectCommand);

        expect($repository->count())->toBe(1);
        expect($repository->hasProject($project->getId()))->toBeTrue();

        $savedProject = $repository->getProject($project->getId());
        expect($savedProject->getId()->equals($project->getId()))->toBeTrue();
        expect((string)$savedProject->getName())->toBe((string)$registerProjectCommand->name);
    });

    test('register project handler records ProjectCreatedEvent', function (): void {
        $repository = new InMemoryProjectRepository();
        $handler = new RegisterProjectHandler($repository);
        $registerProjectCommand = ProjectTestFactory::createValidRegisterProjectCommand([
            'name' => 'Test Project'
        ]);

        $project = $handler($registerProjectCommand);

        $events = $repository->getEventsForProject($project->getId());
        ProjectEventAsserter::assertEventCount($events, 1);
        ProjectEventAsserter::assertProjectCreatedEvent(
            $events[0],
            $project->getId(),
            $registerProjectCommand->name,
            $registerProjectCommand->ownerId
        );
    });

    test('register project handler returns created project', function (): void {
        $repository = new InMemoryProjectRepository();
        $handler = new RegisterProjectHandler($repository);
        $projectName = ProjectTestFactory::createProjectName('Returned Project');
        $uuid = ProjectTestFactory::createValidUuid();
        $registerProjectCommand = ProjectTestFactory::createValidRegisterProjectCommand([
            'name' => (string)$projectName,
            'ownerId' => (string)$uuid
        ]);

        $project = $handler($registerProjectCommand);

        expect((string)$project->getName())->toBe((string)$projectName);
        expect($project->getOwnerId()->equals($uuid))->toBeTrue();
        expect($project->getCreatedAt())->toBeInstanceOf(DateTimeImmutable::class);
        expect($project->getWorkers())->toBeEmpty();
    });

    test('multiple projects can be registered', function (): void {
        $repository = new InMemoryProjectRepository();
        $handler = new RegisterProjectHandler($repository);

        $registerProjectCommand = ProjectTestFactory::createValidRegisterProjectCommand([
            'name' => 'Project One'
        ]);
        $command2 = ProjectTestFactory::createValidRegisterProjectCommand([
            'name' => 'Project Two'
        ]);

        $project1 = $handler($registerProjectCommand);
        $project2 = $handler($command2);

        expect($repository->count())->toBe(2);
        expect($project1->getId()->equals($project2->getId()))->toBeFalse();
        expect((string)$project1->getName())->toBe('Project One');
        expect((string)$project2->getName())->toBe('Project Two');
    });
});
