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
        if ($events !== []) {
            if (!isset($this->savedEvents[$id])) {
                $this->savedEvents[$id] = [];
            }
            $this->savedEvents[$id] = array_merge($this->savedEvents[$id], $events);
        }

        $project->clearDomainEvents();
    }

    public function load(Uuid $uuid): ?Project
    {
        $id = (string)$uuid;
        return $this->projects[$id] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->projects);
    }

    public function exists(Uuid $uuid): bool
    {
        $id = (string)$uuid;
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

    public function getEventsForProject(Uuid $uuid): array
    {
        $id = (string)$uuid;
        return $this->savedEvents[$id] ?? [];
    }

    public function hasProject(Uuid $uuid): bool
    {
        return $this->exists($uuid);
    }

    public function getProject(Uuid $uuid): ?Project
    {
        return $this->load($uuid);
    }
}
