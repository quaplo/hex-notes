<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

use App\Project\Domain\Model\Project;
use App\Project\Domain\Repository\ProjectRepositoryInterface;

final readonly class RegisterProjectHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository
    ) {
    }

    public function __invoke(RegisterProjectCommand $registerProjectCommand): Project
    {
        $project = Project::create($registerProjectCommand->name, $registerProjectCommand->ownerId);
        $this->projectRepository->save($project);

        return $project;
    }
}
