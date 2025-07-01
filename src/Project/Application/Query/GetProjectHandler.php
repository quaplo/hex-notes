<?php

declare(strict_types=1);

namespace App\Project\Application\Query;

use App\Infrastructure\Http\Dto\ProjectDto;
use App\Infrastructure\Http\Mapper\ProjectDtoMapper;
use App\Project\Application\ProjectService;

final readonly class GetProjectHandler
{
    public function __construct(
        private ProjectService $projectService,
        private ProjectDtoMapper $mapper
    ) {
    }

    public function __invoke(GetProjectQuery $query): ProjectDto
    {
        $project = $this->projectService->getProject($query->getId()->toString());

        if (!$project) {
            throw new \RuntimeException('Project not found');
        }

        return $this->mapper->toDto($project);
    }
}
