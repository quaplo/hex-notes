<?php

declare(strict_types=1);

namespace App\Project\Application\Command\Delete;

use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Model\Project;
use App\Project\Domain\Repository\ProjectRepositoryInterface;

final readonly class DeleteProjectHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository
    ) {
    }

    public function __invoke(DeleteProjectCommand $deleteProjectCommand): Project
    {
        $project = $this->projectRepository->load($deleteProjectCommand->projectId);

        if (!$project instanceof Project) {
            throw new ProjectNotFoundException($deleteProjectCommand->projectId);
        }

        $deletedProject = $project->delete();
        $this->projectRepository->save($deletedProject);

        return $deletedProject;
    }
}
