<?php

declare(strict_types=1);

namespace App\Project\Domain\Model;

use App\Project\Domain\ValueObject\ProjectName;
use App\Project\Domain\ValueObject\ProjectRole;
use App\Project\Domain\ValueObject\ProjectWorker;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final class ProjectSnapshotFactory
{
    /**
     * Create a snapshot from Project aggregate
     */
    public function createSnapshot(Project $project, int $version): ProjectSnapshot
    {
        $workersData = [];
        foreach ($project->getWorkers() as $projectWorker) {
            $workersData[] = [
                'userId' => $projectWorker->getUserId()->toString(),
                'role' => (string) $projectWorker->getRole(),
                'createdAt' => $projectWorker->getCreatedAt()->format(DateTimeImmutable::ATOM),
                'addedBy' => $projectWorker->getAddedBy()->toString(),
            ];
        }

        $projectData = [
            'id' => $project->getId()->toString(),
            'name' => (string) $project->getName(),
            'ownerId' => $project->getOwnerId()->toString(),
            'createdAt' => $project->getCreatedAt()->format(DateTimeImmutable::ATOM),
            'deletedAt' => $project->getDeletedAt()?->format(DateTimeImmutable::ATOM),
            'workers' => $workersData,
        ];

        return ProjectSnapshot::create(
            $project->getId(),
            $version,
            $projectData
        );
    }

    /**
     * Restore Project aggregate from snapshot
     */
    public function restoreFromSnapshot(ProjectSnapshot $projectSnapshot): Project
    {
        $data = $projectSnapshot->getData();

        // Create Project with restored state
        $project = new Project(
            Uuid::create($data['id']),
            new ProjectName($data['name']),
            new DateTimeImmutable($data['createdAt']),
            Uuid::create($data['ownerId']),
            isset($data['deletedAt']) ? new DateTimeImmutable($data['deletedAt']) : null
        );

        // Restore workers
        foreach ($data['workers'] as $workerData) {
            $worker = ProjectWorker::create(
                Uuid::create($workerData['userId']),
                ProjectRole::create($workerData['role']),
                Uuid::create($workerData['addedBy']),
                new DateTimeImmutable($workerData['createdAt'])
            );
            
            // Use reflection to add worker directly to avoid domain events
            $project->restoreWorker($worker);
        }

        // Set the version from snapshot
        $project->restoreVersion($projectSnapshot->getVersion());

        return $project;
    }
}