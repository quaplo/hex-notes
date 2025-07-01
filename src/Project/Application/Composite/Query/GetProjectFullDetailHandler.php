<?php

declare(strict_types=1);

namespace App\Project\Application\Composite\Query;

use App\Project\Application\Composite\Dto\ProjectFullDetailDto;
use App\Project\Application\Composite\Mapper\ProjectFullDetailDtoMapper;
use App\Project\Application\Query\GetProjectHandler;
use App\Project\Application\Query\GetProjectQuery;
use App\User\Application\Query\GetUserByIdHandler;
use App\User\Application\Query\GetUserByIdQuery;
use App\Project\Application\ProjectService;

final readonly class GetProjectFullDetailHandler
{
    public function __construct(
        private GetProjectHandler $getProjectHandler,
        private GetUserByIdHandler $getUserHandler,
        private ProjectService $projectService,
        private ProjectFullDetailDtoMapper $detailDtoMapper,
    ) {
    }

    public function __invoke(GetProjectFullDetailQuery $query): ProjectFullDetailDto
    {
        $projectDto = ($this->getProjectHandler)(new GetProjectQuery($query->id));
        $userDto = ($this->getUserHandler)(new GetUserByIdQuery($projectDto->ownerId));

        $project = $this->projectService->getProject($query->id);
        $workers = [];
        if ($project) {
            foreach ($project->getWorkers() as $worker) {
                $userDto = ($this->getUserHandler)(new GetUserByIdQuery($worker->getUserId()->toString()));
                if ($userDto) {
                    $workers[] = $userDto;
                }
            }
        }

        return $this->detailDtoMapper->toDto($projectDto, $userDto, $workers);
    }
}
