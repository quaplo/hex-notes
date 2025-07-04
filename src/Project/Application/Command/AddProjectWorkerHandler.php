<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Model\Project;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Domain\ValueObject\ProjectWorker;

final readonly class AddProjectWorkerHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository
    ) {
    }

    public function __invoke(AddProjectWorkerCommand $command): Project
    {
        $project = $this->projectRepository->load($command->projectId);
        if (!$project) {
            throw new ProjectNotFoundException($command->projectId);
        }

        $worker = ProjectWorker::create(
            $command->userId,
            $command->role,
            $command->addedBy
        );

        $project = $project->addWorker($worker);
        $this->projectRepository->save($project);
        return $project;
    }
}
