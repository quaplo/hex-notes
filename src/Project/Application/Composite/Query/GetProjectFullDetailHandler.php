<?php

declare(strict_types=1);

namespace App\Project\Application\Composite\Query;

use App\Project\Application\Composite\Dto\ProjectFullDetailDto;
use App\Project\Application\Composite\Mapper\ProjectFullDetailDtoMapper;
use App\Project\Application\Query\GetProjectHandler;
use App\Project\Application\Query\GetProjectQuery;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Infrastructure\Http\Mapper\ProjectDtoMapper;
use App\User\Application\Query\GetUserByIdHandler;
use App\User\Application\Query\GetUserByIdQuery;

final readonly class GetProjectFullDetailHandler
{
    public function __construct(
        private GetProjectHandler $getProjectHandler,
        private GetUserByIdHandler $getUserHandler,
        private ProjectRepositoryInterface $projectRepository,
        private ProjectDtoMapper $projectDtoMapper,
        private ProjectFullDetailDtoMapper $detailDtoMapper,
    ) {
    }

    public function __invoke(GetProjectFullDetailQuery $query): ?ProjectFullDetailDto
    {
        $project = ($this->getProjectHandler)(GetProjectQuery::fromPrimitives($query->id->toString()));
        
        if (!$project) {
            return null;
        }
        
        $projectDto = $this->projectDtoMapper->toDto($project);
        $ownerDto = ($this->getUserHandler)(new GetUserByIdQuery($project->getOwnerId()->toString()));

        if (!$ownerDto) {
            return null;
        }

        $workers = [];
        foreach ($project->getWorkers() as $worker) {
            $workerUserDto = ($this->getUserHandler)(new GetUserByIdQuery($worker->getUserId()->toString()));
            if ($workerUserDto) {
                $workers[] = $workerUserDto;
            }
        }

        return $this->detailDtoMapper->toDto($projectDto, $ownerDto, $workers);
    }
}
