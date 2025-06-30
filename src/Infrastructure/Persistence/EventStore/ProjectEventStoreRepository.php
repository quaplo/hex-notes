<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EventStore;

use App\Project\Domain\Model\Project;
use App\Shared\Aggregate\AggregateRoot;

final class ProjectEventStoreRepository extends AbstractEventStoreRepository
{
    protected function createAggregate(): \App\Project\Domain\Model\Project
    {
        return new \App\Project\Domain\Model\Project(
            \App\Shared\ValueObject\Uuid::generate(),
            new \App\Project\Domain\ValueObject\ProjectName('Temporary'),
            new \DateTimeImmutable(),
            \App\Shared\ValueObject\Uuid::generate()
        );
    }
}
