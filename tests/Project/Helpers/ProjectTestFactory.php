<?php

declare(strict_types=1);

namespace App\Tests\Project\Helpers;

use App\Project\Application\Command\Register\RegisterProjectCommand;
use App\Project\Domain\Model\Project;
use App\Project\Domain\ValueObject\ProjectName;
use App\Project\Domain\ValueObject\ProjectRole;
use App\Project\Domain\ValueObject\ProjectWorker;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final class ProjectTestFactory
{
    public static function createProject(array $overrides = []): Project
    {
        $defaults = [
            'id' => Uuid::generate(),
            'name' => new ProjectName('Test Project'),
            'createdAt' => new DateTimeImmutable(),
            'ownerId' => Uuid::generate(),
            'deletedAt' => null,
        ];

        $data = array_merge($defaults, $overrides);

        return new Project(
            $data['id'],
            $data['name'],
            $data['createdAt'],
            $data['ownerId'],
            $data['deletedAt']
        );
    }

    public static function createProjectName(string $name = 'Test Project'): ProjectName
    {
        return new ProjectName($name);
    }

    public static function createProjectWorker(array $overrides = []): ProjectWorker
    {
        $defaults = [
            'userId' => Uuid::generate(),
            'role' => ProjectRole::participant(),
            'addedBy' => Uuid::generate(),
            'addedAt' => new DateTimeImmutable(),
        ];

        $data = array_merge($defaults, $overrides);

        return ProjectWorker::create(
            $data['userId'],
            $data['role'],
            $data['addedBy'],
            $data['addedAt']
        );
    }

    public static function createValidRegisterProjectCommand(array $overrides = []): RegisterProjectCommand
    {
        $defaults = [
            'name' => 'Test Project',
            'ownerId' => (string)Uuid::generate(),
        ];

        $data = array_merge($defaults, $overrides);

        return RegisterProjectCommand::fromPrimitives(
            $data['name'],
            $data['ownerId']
        );
    }

    public static function createValidUuid(): Uuid
    {
        return Uuid::generate();
    }

    public static function createFixedUuid(string $value): Uuid
    {
        return new Uuid($value);
    }

    public static function createDateTime(string $time = 'now'): DateTimeImmutable
    {
        return new DateTimeImmutable($time);
    }
}
