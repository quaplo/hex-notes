<?php

declare(strict_types=1);

namespace App\Project\Application\Composite\Query;

use App\Project\Application\Composite\Dto\ProjectFullDetailDto;
use App\Project\Application\Query\GetProjectHandler;
use App\Project\Application\Query\GetProjectQuery;
use App\User\Application\Query\GetUserByIdHandler;
use App\User\Application\Query\GetUserByIdQuery;

final class GetProjectFullDetailHandler
{
    public function __construct(
        private GetProjectHandler $getProjectHandler,
        private GetUserByIdHandler $getUserHandler,
    ) {
    }

    public function __invoke(GetProjectFullDetailQuery $query): ProjectFullDetailDto
    {
        $projectDto = ($this->getProjectHandler)(new GetProjectQuery($query->id));
        $userDto = ($this->getUserHandler)(new GetUserByIdQuery($projectDto->ownerId));

        return new ProjectFullDetailDto($projectDto, $userDto);
    }
}
