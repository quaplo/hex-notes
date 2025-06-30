<?php

declare(strict_types=1);

namespace App\Project\Application;

use App\Infrastructure\Persistence\EventStore\ProjectEventStoreRepository;
use App\Project\Domain\Model\Project;
use App\Project\Domain\ValueObject\ProjectName;
use App\Project\Domain\ValueObject\ProjectOwner;
use App\Project\Domain\ValueObject\UserId;
use App\Shared\ValueObject\Email;
use App\Shared\ValueObject\Uuid;
use App\User\Application\UserEventSourcingService;

final class ProjectService
{
    public function __construct(
        private readonly ProjectEventStoreRepository $projectRepository,
        private readonly UserEventSourcingService $userService
    ) {
    }

    public function createProject(string $name, string $ownerEmail): Project
    {
        $user = $this->userService->getUserByEmail(new Email($ownerEmail));

        if (!$user) {
            throw new \DomainException("User with email $ownerEmail not found");
        }

        $projectOwner = ProjectOwner::create(
            UserId::fromUuid($user->getId()),
            $user->getEmail()
        );

        $project = Project::create(
            new ProjectName($name),
            $projectOwner
        );

        $this->projectRepository->save($project);

        return $project;
    }

    public function renameProject(string $projectId, string $newName): Project
    {
        $project = $this->projectRepository->load(new Uuid($projectId));
        
        if (!$project) {
            throw new \DomainException("Project with id $projectId not found");
        }

        $renamedProject = $project->rename(new ProjectName($newName));
        $this->projectRepository->save($renamedProject);

        return $renamedProject;
    }

    public function deleteProject(string $projectId): Project
    {
        $project = $this->projectRepository->load(new Uuid($projectId));
        
        if (!$project) {
            throw new \DomainException("Project with id $projectId not found");
        }

        $deletedProject = $project->delete();
        $this->projectRepository->save($deletedProject);

        return $deletedProject;
    }

    public function getProject(string $projectId): ?Project
    {
        return $this->projectRepository->load(new Uuid($projectId));
    }

    public function getProjectHistory(string $projectId): array
    {
        $project = $this->projectRepository->load(new Uuid($projectId));
        
        if (!$project) {
            return [];
        }

        return [
            'project' => $project,
            'events' => $project->getDomainEvents()
        ];
    }
}
