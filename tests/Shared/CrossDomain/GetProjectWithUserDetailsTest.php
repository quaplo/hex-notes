<?php

declare(strict_types=1);

namespace Tests\Shared\CrossDomain;

use DateTimeImmutable;
use ReflectionClass;
use RuntimeException;
use App\Shared\Application\Dto\ProjectDto;
use App\User\Application\Dto\UserDto;
use App\Shared\Application\Mapper\ProjectDtoMapperInterface;
use App\Project\Application\Query\GetProjectQuery;
use App\Project\Domain\Model\Project;
use App\Project\Domain\ValueObject\ProjectName;
use App\Project\Domain\ValueObject\ProjectWorker;
use App\Project\Domain\ValueObject\ProjectRole;
use App\Shared\Application\CrossDomain\Dto\ProjectWithUserDetailsDto;
use App\Shared\Application\CrossDomain\Query\GetProjectWithUserDetailsHandler;
use App\Shared\Application\CrossDomain\Query\GetProjectWithUserDetailsQuery;
use App\Shared\Application\QueryBus;
use App\Shared\ValueObject\Uuid;
use App\User\Application\Query\GetUserByIdQuery;

it('can get project with user details via cross-domain query', function (): void {
    // Mock project data
    $uuid = Uuid::generate();
    $ownerId = Uuid::create('550e8400-e29b-41d4-a716-446655440001');
    $workerId = Uuid::create('550e8400-e29b-41d4-a716-446655440002');

    // Create a real Project object for testing
    $project = new Project(
        $uuid,
        new ProjectName('Test Project'),
        new DateTimeImmutable('2024-01-01 00:00:00'),
        $ownerId
    );

    // Add a worker using reflection to bypass business rules for testing
    $reflectionClass = new ReflectionClass($project);
    $reflectionProperty = $reflectionClass->getProperty('workers');
    $reflectionProperty->setAccessible(true);
    $reflectionProperty->setValue($project, [
        ProjectWorker::create($workerId, ProjectRole::participant(), $ownerId, new DateTimeImmutable('2024-01-01 00:00:00'))
    ]);

    $ownerDto = new UserDto(
        id: '550e8400-e29b-41d4-a716-446655440001',
        email: 'owner@test.com',
        isDeleted: false
    );

    $workerDto = new UserDto(
        id: '550e8400-e29b-41d4-a716-446655440002',
        email: 'worker@test.com',
        isDeleted: false
    );


    // Mock QueryBus
    $queryBus = new readonly class ($project, $ownerDto, $workerDto) implements QueryBus {
        public function __construct(private Project $project, private UserDto $ownerDto, private UserDto $workerDto)
        {
        }

        public function dispatch(object $query): mixed
        {
            return match ($query::class) {
                GetProjectQuery::class => $this->project,
                GetUserByIdQuery::class => $query->userId === $this->project->getOwnerId()->toString()
                    ? $this->ownerDto
                    : $this->workerDto,
                default => null
            };
        }
    };

    // Mock ProjectDtoMapper
    $expectedProjectDto = new ProjectDto(
        id: $uuid->toString(),
        name: 'Test Project',
        ownerId: $ownerId->toString(),
        workers: [],
        isDeleted: false
    );

    $projectDtoMapper = new readonly class ($expectedProjectDto) implements ProjectDtoMapperInterface {
        public function __construct(private ProjectDto $projectDto)
        {
        }

        public function toDto(Project $project): ProjectDto
        {
            return $this->projectDto;
        }
    };

    $handler = new GetProjectWithUserDetailsHandler($queryBus, $projectDtoMapper);
    $query = new GetProjectWithUserDetailsQuery($uuid);

    $result = $handler($query);

    expect($result)->toBeInstanceOf(ProjectWithUserDetailsDto::class)
        ->and($result->project)->toBeInstanceOf(ProjectDto::class)
        ->and($result->owner)->toEqual($ownerDto)
        ->and($result->workers)->toHaveCount(1)
        ->and($result->workers[0])->toEqual($workerDto);
});

it('returns null when project not found', function (): void {
    $queryBus = new class () implements QueryBus {
        public function dispatch(object $query): mixed
        {
            return null; // Project not found
        }
    };

    $projectDtoMapper = new class () implements ProjectDtoMapperInterface {
        public function toDto(Project $project): ProjectDto
        {
            // This should never be called in this test
            throw new RuntimeException('ProjectDtoMapper should not be called when project is null');
        }
    };

    $handler = new GetProjectWithUserDetailsHandler($queryBus, $projectDtoMapper);
    $query = new GetProjectWithUserDetailsQuery(Uuid::generate());

    $result = $handler($query);

    expect($result)->toBeNull();
});
