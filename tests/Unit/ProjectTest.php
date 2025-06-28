<?php

declare(strict_types=1);

use App\Domain\Project\Model\Project;
use App\Domain\Project\ValueObject\ProjectName;
use App\Shared\ValueObject\Uuid;

test('creates a project with id and createdAt', function () {
    $name = new ProjectName('Test Project');
    $project = Project::create($name);

    expect($project->getId())->toBeInstanceOf(Uuid::class);
    expect((string)$project->getName())->toBe('Test Project');
    expect($project->getCreatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
    expect($project->isDeleted())->toBeFalse();
});

test('deletes project with soft delete', function () {
    $project = Project::create(new ProjectName('X'));
    expect($project->isDeleted())->toBeFalse();

    $deleted = $project->delete();

    expect($deleted->isDeleted())->toBeTrue();
    expect($deleted->getDeletedAt())->toBeInstanceOf(\DateTimeImmutable::class);
});

test('rename checks rules', function () {
    $project = Project::create(new ProjectName('Old'));

    $project->rename(new ProjectName('New'));
    expect((string)$project->getName())->toBe('New');
});
