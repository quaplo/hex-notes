<?php

declare(strict_types=1);

use App\Project\Application\Command\RegisterProjectCommand;
use App\Project\Application\Command\RegisterProjectHandler;
use App\Project\Domain\Event\ProjectCreatedEvent;
use App\Project\Domain\Model\Project;
use App\Tests\Project\Doubles\InMemoryProjectRepository;
use App\Tests\Project\Helpers\ProjectEventAsserter;
use App\Tests\Project\Helpers\ProjectTestFactory;

describe('RegisterProjectHandler', function () {
    
    test('register project handler creates new project', function () {
        $repository = new InMemoryProjectRepository();
        $handler = new RegisterProjectHandler($repository);
        $command = ProjectTestFactory::createValidRegisterProjectCommand([
            'name' => 'New Project'
        ]);

        $project = $handler($command);

        expect($project)->toBeInstanceOf(Project::class);
        expect((string)$project->getName())->toBe('New Project');
        expect($project->getOwnerId()->equals($command->ownerId))->toBeTrue();
        expect($project->isDeleted())->toBeFalse();
    });

    test('register project handler saves project via repository', function () {
        $repository = new InMemoryProjectRepository();
        $handler = new RegisterProjectHandler($repository);
        $command = ProjectTestFactory::createValidRegisterProjectCommand();

        $project = $handler($command);

        expect($repository->count())->toBe(1);
        expect($repository->hasProject($project->getId()))->toBeTrue();
        
        $savedProject = $repository->getProject($project->getId());
        expect($savedProject->getId()->equals($project->getId()))->toBeTrue();
        expect((string)$savedProject->getName())->toBe((string)$command->name);
    });

    test('register project handler records ProjectCreatedEvent', function () {
        $repository = new InMemoryProjectRepository();
        $handler = new RegisterProjectHandler($repository);
        $command = ProjectTestFactory::createValidRegisterProjectCommand([
            'name' => 'Test Project'
        ]);

        $project = $handler($command);

        $events = $repository->getEventsForProject($project->getId());
        ProjectEventAsserter::assertEventCount($events, 1);
        ProjectEventAsserter::assertProjectCreatedEvent(
            $events[0], 
            $project->getId(), 
            $command->name, 
            $command->ownerId
        );
    });

    test('register project handler returns created project', function () {
        $repository = new InMemoryProjectRepository();
        $handler = new RegisterProjectHandler($repository);
        $projectName = ProjectTestFactory::createProjectName('Returned Project');
        $ownerId = ProjectTestFactory::createValidUuid();
        $command = ProjectTestFactory::createValidRegisterProjectCommand([
            'name' => (string)$projectName,
            'ownerId' => (string)$ownerId
        ]);

        $project = $handler($command);

        expect((string)$project->getName())->toBe((string)$projectName);
        expect($project->getOwnerId()->equals($ownerId))->toBeTrue();
        expect($project->getCreatedAt())->toBeInstanceOf(DateTimeImmutable::class);
        expect($project->getWorkers())->toBeEmpty();
    });

    test('multiple projects can be registered', function () {
        $repository = new InMemoryProjectRepository();
        $handler = new RegisterProjectHandler($repository);
        
        $command1 = ProjectTestFactory::createValidRegisterProjectCommand([
            'name' => 'Project One'
        ]);
        $command2 = ProjectTestFactory::createValidRegisterProjectCommand([
            'name' => 'Project Two'
        ]);

        $project1 = $handler($command1);
        $project2 = $handler($command2);

        expect($repository->count())->toBe(2);
        expect($project1->getId()->equals($project2->getId()))->toBeFalse();
        expect((string)$project1->getName())->toBe('Project One');
        expect((string)$project2->getName())->toBe('Project Two');
    });

});