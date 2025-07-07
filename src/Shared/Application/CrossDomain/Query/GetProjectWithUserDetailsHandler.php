<?php

declare(strict_types=1);

namespace App\Shared\Application\CrossDomain\Query;

use App\Project\Application\Query\GetProjectQuery;
use App\Shared\Application\CrossDomain\Dto\ProjectWithUserDetailsDto;
use App\Shared\Application\Mapper\ProjectDtoMapperInterface;
use App\Shared\Application\QueryBus;
use App\User\Application\Query\GetUserByIdQuery;

final readonly class GetProjectWithUserDetailsHandler
{
    public function __construct(
        private QueryBus $queryBus,
        private ProjectDtoMapperInterface $projectDtoMapper
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
        
        // Map project to DTO
        $projectDto = $this->projectDtoMapper->toDto($project);
        
        // Get owner from User domain
        $owner = $this->queryBus->dispatch(
            new GetUserByIdQuery($project->getOwnerId()->toString())
        );
        
        // Get all workers from User domain
        $workers = [];
        foreach ($project->getWorkers() as $worker) {
            $workerUser = $this->queryBus->dispatch(
                new GetUserByIdQuery($worker->getUserId()->toString())
            );
            if ($workerUser) {
                $workers[] = $workerUser;
            }
        }
        
        return new ProjectWithUserDetailsDto($projectDto, $owner, $workers);
    }
}