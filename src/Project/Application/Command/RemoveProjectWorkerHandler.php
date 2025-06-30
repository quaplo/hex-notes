<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

use App\Project\Application\ProjectService;
use App\Project\Domain\Model\Project;
use App\Shared\ValueObject\Uuid;

final class RemoveProjectWorkerHandler
{
    public function __construct(
        private readonly ProjectService $projectService
    ) {}

    public function __invoke(RemoveProjectWorkerCommand $command): Project
    {
        $project = $this->projectService->getProject($command->projectId);
        if (!$project) {
            throw new \DomainException('Project not found');
        }

        $project = $project->removeWorkerByUserId(
            Uuid::create($command->userId),
            $command->removedBy ? Uuid::create($command->removedBy) : null
        );
        $this->projectService->save($project);
        return $project;
    }
} 