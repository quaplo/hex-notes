<?php

declare(strict_types=1);

namespace App\Project\Application;

use App\Infrastructure\Persistence\EventStore\ProjectEventStoreRepository;
use App\Project\Domain\Model\Project;
use App\Project\Domain\ValueObject\ProjectName;
use App\Shared\ValueObject\Uuid;

final readonly class ProjectService
{
    public function __construct(
        private ProjectEventStoreRepository $projectRepository
    ) {
    }

    public function createProject(string $name, Uuid $ownerId): Project
    {
        $project = Project::create(new ProjectName($name), $ownerId);
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

    public function save(Project $project): void
    {
        $this->projectRepository->save($project);
    }
}
