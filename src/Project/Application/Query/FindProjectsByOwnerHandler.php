<?php

declare(strict_types=1);

namespace App\Project\Application\Query;

use App\Project\Domain\Model\Project;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Event\EventStore;

final readonly class FindProjectsByOwnerHandler
{
    public function __construct(
        private EventStore $eventStore,
        private ProjectRepositoryInterface $projectRepository
    ) {
    }

    /**
     * @return Project[]
     */
    public function __invoke(FindProjectsByOwnerQuery $query): array
    {
        $aggregateIds = $this->eventStore->findProjectAggregatesByOwnerId($query->getOwnerId());
        
        $projects = [];
        foreach ($aggregateIds as $aggregateId) {
            $project = $this->projectRepository->load($aggregateId);
            if ($project && !$project->isDeleted()) {
                $projects[] = $project;
            }
        }
        
        return $projects;
    }
}