<?php

declare(strict_types=1);

namespace App\Project\Application\Query;

use App\Project\Domain\Model\Project;
use App\Project\Domain\Repository\ProjectRepositoryInterface;

final readonly class GetProjectHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository
    ) {
    }

    public function __invoke(GetProjectQuery $getProjectQuery): ?Project
    {
        $project = $this->projectRepository->load($getProjectQuery->projectId);

        if (!$project || $project->isDeleted()) {
            return null;
        }

        return $project;
    }
}
