<?php

declare(strict_types=1);

namespace App\Application\Project;

use App\Domain\Project\Model\Project;
use App\Domain\Project\Model\ProjectWorker;
use App\Domain\Project\Repository\ProjectRepositoryInterface;
use App\Domain\Project\ValueObject\ProjectName;
use App\Domain\Project\ValueObject\ProjectOwner;
use App\Domain\Project\ValueObject\ProjectRole;
use App\Domain\Project\ValueObject\UserId;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Shared\ValueObject\Email;
use App\Application\Exception\UserNotFoundException;
use App\Shared\ValueObject\Uuid;

final class ProjectService
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function registerProjectWithOwner(ProjectName $name, Email $ownerEmail): Project
    {
        $user = $this->userRepository->findByEmail($ownerEmail);

        if (!$user) {
            throw new UserNotFoundException(sprintf('User with email %s not found', $ownerEmail));
        }

        $projectOwner = ProjectOwner::create(UserId::fromUuid($user->getId()), $user->getEmail());

        $project = Project::create($name, $projectOwner);

        $project->addWorker(
            ProjectWorker::create(
                $user->getId(),
                ProjectRole::owner()
            )
        );

        $this->projectRepository->save($project);

        return $project;
    }

    public function getProject(Uuid $id): ?Project
    {
        return $this->projectRepository->findById($id);
    }
}
