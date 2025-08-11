<?php

declare(strict_types=1);

namespace App\Project\Application\Command\Worker;

use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Model\Project;
use App\Project\Domain\Repository\ProjectRepositoryInterface;

final readonly class RemoveProjectWorkerHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
    ) {
    }

    public function __invoke(RemoveProjectWorkerCommand $removeProjectWorkerCommand): Project
    {
        $project = $this->projectRepository->load($removeProjectWorkerCommand->getProjectId());

        if (!$project instanceof Project) {
            throw new ProjectNotFoundException($removeProjectWorkerCommand->getProjectId());
        }

        $project = $project->removeWorkerByUserId(
            $removeProjectWorkerCommand->getUserId(),
            $removeProjectWorkerCommand->getRemovedBy()
        );
        $this->projectRepository->save($project);

        return $project;
    }
}
