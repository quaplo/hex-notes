<?php

declare(strict_types=1);

namespace App\Application\Project\Query;

use App\Application\Project\EventSourcingService;
use App\Infrastructure\Http\Dto\ProjectDto;
use App\Infrastructure\Http\Mapper\ProjectDtoMapper;

final class GetProjectHandler
{
    public function __construct(
        private readonly EventSourcingService $projectService,
        private readonly ProjectDtoMapper $mapper
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
