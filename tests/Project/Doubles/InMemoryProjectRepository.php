<?php

declare(strict_types=1);

namespace App\Tests\Project\Doubles;

use App\Project\Domain\Model\Project;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\ValueObject\Uuid;

final class InMemoryProjectRepository implements ProjectRepositoryInterface
{
    /** @var array<string, Project> */
    private array $projects = [];
    
    /** @var array<string, array> */
    private array $savedEvents = [];

    public function save(Project $project): void
    {
        $id = (string)$project->getId();
        $this->projects[$id] = $project;
        
        // Track events for testing purposes
        $events = $project->getDomainEvents();
        if (!empty($events)) {
            if (!isset($this->savedEvents[$id])) {
                $this->savedEvents[$id] = [];
            }
            $this->savedEvents[$id] = array_merge($this->savedEvents[$id], $events);
        }
        
        $project->clearDomainEvents();
    }

    public function load(Uuid $aggregateId): ?Project
    {
        $id = (string)$aggregateId;
        return $this->projects[$id] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->projects);
    }

    public function exists(Uuid $aggregateId): bool
    {
        $id = (string)$aggregateId;
        return isset($this->projects[$id]);
    }

    // Testing helper methods
    
    public function clear(): void
    {
        $this->projects = [];
        $this->savedEvents = [];
    }

    public function count(): int
    {
        return count($this->projects);
    }

    /**
     * @return array<string, array>
     */
    public function getSavedEvents(): array
    {
        return $this->savedEvents;
    }

    public function getEventsForProject(Uuid $projectId): array
    {
        $id = (string)$projectId;
        return $this->savedEvents[$id] ?? [];
    }

    public function hasProject(Uuid $projectId): bool
    {
        return $this->exists($projectId);
    }

    public function getProject(Uuid $projectId): ?Project
    {
        return $this->load($projectId);
    }
}