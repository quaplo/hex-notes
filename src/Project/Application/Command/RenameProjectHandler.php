<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Model\Project;
use App\Project\Domain\Repository\ProjectRepositoryInterface;

final readonly class RenameProjectHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository
    ) {
    }

    public function __invoke(RenameProjectCommand $command): Project
    {
        $project = $this->projectRepository->load($command->projectId);

        if (!$project) {
            throw new ProjectNotFoundException($command->projectId);
        }

        $renamedProject = $project->rename($command->newName);
        $this->projectRepository->save($renamedProject);

        return $renamedProject;
    }
}