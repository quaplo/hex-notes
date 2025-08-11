<?php

declare(strict_types=1);

namespace App\Project\Application\Command\Worker;

use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Model\Project;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Domain\ValueObject\ProjectWorker;

final readonly class AddProjectWorkerHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
    ) {
    }

    public function __invoke(AddProjectWorkerCommand $addProjectWorkerCommand): Project
    {
        $project = $this->projectRepository->load($addProjectWorkerCommand->getProjectId());

        if (!$project instanceof Project) {
            throw new ProjectNotFoundException($addProjectWorkerCommand->getProjectId());
        }

        $projectWorker = ProjectWorker::create(
            $addProjectWorkerCommand->getUserId(),
            $addProjectWorkerCommand->getRole(),
            $addProjectWorkerCommand->getAddedBy()
        );

        $project = $project->addWorker($projectWorker);
        $this->projectRepository->save($project);

        return $project;
    }
}
