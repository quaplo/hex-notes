<?php

declare(strict_types=1);

namespace App\Application\Project;

use App\Domain\Project\Model\Project;
use App\Domain\Project\ValueObject\ProjectName;
use App\Domain\Project\ValueObject\ProjectOwner;
use App\Domain\Project\ValueObject\UserId;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Persistence\EventStore\ProjectEventStoreRepository;
use App\Shared\ValueObject\Email;
use App\Shared\ValueObject\Uuid;

final class EventSourcingService
{
    public function __construct(
        private readonly ProjectEventStoreRepository $projectRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    public function createProject(string $name, string $ownerEmail): Project
    {
        $user = $this->userRepository->findByEmail(new Email($ownerEmail));

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
        // This would typically be implemented in a separate read model
        // For now, we'll just return the project with its events
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
