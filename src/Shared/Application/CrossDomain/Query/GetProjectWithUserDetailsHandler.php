<?php

declare(strict_types=1);

namespace App\Shared\Application\CrossDomain\Query;

use App\Project\Application\Query\Get\GetProjectQuery;
use App\Shared\Application\CrossDomain\Dto\ProjectWithUserDetailsDto;
use App\Shared\Application\QueryBus;
use App\User\Application\Query\GetUserByIdQuery;

final readonly class GetProjectWithUserDetailsHandler
{
    public function __construct(
        private QueryBus $queryBus
    ) {
    }

    public function __invoke(GetProjectWithUserDetailsQuery $getProjectWithUserDetailsQuery): ?ProjectWithUserDetailsDto
    {
        // Get project from Project domain
        $project = $this->queryBus->dispatch(
            GetProjectQuery::fromPrimitives($getProjectWithUserDetailsQuery->projectId->toString())
        );

        if (!$project) {
            return null;
        }

        // Get owner from User domain
        $owner = $this->queryBus->dispatch(
            new GetUserByIdQuery($project->getOwnerId()->toString())
        );

        if (!$owner) {
            return null; // Cannot create project without owner
        }

        // Get all workers from User domain, filtering out deleted users
        $workers = [];
        foreach ($project->getWorkers() as $worker) {
            $workerUser = $this->queryBus->dispatch(
                new GetUserByIdQuery($worker->getUserId()->toString())
            );
            if ($workerUser && !$workerUser->isDeleted) {
                $workers[] = [
                    'id' => $workerUser->id,
                    'email' => $workerUser->email,
                    'isDeleted' => $workerUser->isDeleted,
                    'addedBy' => $worker->getAddedBy()->toString(),
                    'addedAt' => $worker->getCreatedAt()->format('Y-m-d H:i:s'),
                    'role' => $worker->getRole()->__toString()
                ];
            }
        }

        return ProjectWithUserDetailsDto::create(
            id: $project->getId()->toString(),
            name: $project->getName()->__toString(),
            owner: $owner,
            workers: $workers,
            isDeleted: $project->isDeleted()
        );
    }
}
