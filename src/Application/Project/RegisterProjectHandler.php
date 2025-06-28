<?php

declare(strict_types=1);

namespace App\Application\Project;

use App\Domain\Project\Model\Project;
use App\Domain\Project\Repository\ProjectRepositoryInterface;
use App\Domain\Project\ValueObject\ProjectName;

final readonly class RegisterProjectHandler
{
    public function __construct(
        private ProjectRepositoryInterface $repository
    ) {
    }

    public function handle(string $name): Project
    {
        $project = Project::create(new ProjectName($name));

        $this->repository->save($project);

        return $project;
    }
}
