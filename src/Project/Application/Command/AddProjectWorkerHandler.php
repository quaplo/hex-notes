<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

use App\Project\Domain\Model\Project;
use App\Project\Domain\ValueObject\ProjectWorker;
use App\Project\Domain\ValueObject\ProjectRole;
use App\Project\Application\ProjectService;
use App\Shared\ValueObject\Uuid;

final readonly class AddProjectWorkerHandler
{
    public function __construct(
        private ProjectService $projectService
    ) {
    }

    public function __invoke(AddProjectWorkerCommand $command): Project
    {
        $project = $this->projectService->getProject($command->projectId);
        if (!$project) {
            throw new \DomainException('Project not found');
        }

        $worker = ProjectWorker::create(
            $command->userId,
            $command->role,
            $command->addedBy
        );

        $project = $project->addWorker($worker);
        $this->projectService->save($project);
        return $project;
    }
}
