<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EventStore;

use App\Project\Domain\Model\Project;
use App\Shared\Aggregate\AggregateRoot;

final class ProjectEventStoreRepository extends AbstractEventStoreRepository
{
    protected function createAggregate(): AggregateRoot
    {
        // Create an empty project instance for event replay
        return new Project(
            id: \App\Shared\ValueObject\Uuid::generate(), // This will be overridden during replay
            name: new \App\Project\Domain\ValueObject\ProjectName('Temporary'), // Valid name for replay
            createdAt: new \DateTimeImmutable(), // This will be overridden during replay
            owner: new \App\Project\Domain\ValueObject\ProjectOwner(
                \App\Project\Domain\ValueObject\UserId::generate(),
                new \App\Shared\ValueObject\Email('temp@example.com')
            ) // This will be overridden during replay
        );
    }
}
