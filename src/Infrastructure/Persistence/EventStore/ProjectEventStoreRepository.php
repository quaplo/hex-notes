<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EventStore;

use Exception;
use App\Shared\Domain\Model\AggregateSnapshot;
use App\Project\Domain\Model\Project;
use App\Project\Domain\Model\ProjectSnapshotFactory;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Event\EventDispatcher;
use App\Shared\Event\EventStore;
use App\Shared\Event\SnapshotStore;
use App\Shared\Event\SnapshotStrategy;
use App\Shared\ValueObject\Uuid;

final readonly class ProjectEventStoreRepository implements ProjectRepositoryInterface
{
    private const AGGREGATE_TYPE = 'Project';

    public function __construct(
        private EventStore $eventStore,
        private EventDispatcher $eventDispatcher,
        private SnapshotStore $snapshotStore,
        private ProjectSnapshotFactory $projectSnapshotFactory,
        private SnapshotStrategy $snapshotStrategy
    ) {
    }

    public function save(Project $project): void
    {
        $events = $project->getDomainEvents();

        if ($events === []) {
            return;
        }

        $expectedVersion = $project->getVersion();

        // Save events to event store
        $this->eventStore->append(
            $project->getId(),
            $events,
            $expectedVersion
        );

        // Calculate new version after events
        $newVersion = $expectedVersion + count($events);

        // Check if we should create a snapshot
        if ($this->snapshotStrategy->shouldCreateSnapshot($project->getId(), $newVersion)) {
            try {
                $snapshot = $this->projectSnapshotFactory->createSnapshot($project, $newVersion);
                $this->snapshotStore->save($snapshot);
            } catch (Exception $e) {
                // Log error but don't fail the save operation
                // Snapshots are for performance optimization, not critical for correctness
                error_log("Failed to create snapshot for project {$project->getId()}: " . $e->getMessage());
            }
        }

        // Dispatch events and clear domain events
        $this->eventDispatcher->dispatch($events);
        $project->clearDomainEvents();
    }

    public function load(Uuid $uuid): ?Project
    {
        // Try to load from snapshot first
        $snapshot = $this->snapshotStore->loadLatest($uuid, self::AGGREGATE_TYPE);
        
        if ($snapshot instanceof AggregateSnapshot) {
            // Restore project from snapshot
            $project = $this->projectSnapshotFactory->restoreFromSnapshot($snapshot);
            
            // Load events after the snapshot version
            $eventsAfterSnapshot = $this->eventStore->getEventsFromVersion(
                $uuid,
                $snapshot->getVersion() + 1
            );
            
            // Replay events after snapshot
            foreach ($eventsAfterSnapshot as $eventAfterSnapshot) {
                $project->replayEvent($eventAfterSnapshot);
            }
            
            return $project;
        }

        // No snapshot found, load all events (traditional event sourcing)
        $events = $this->eventStore->getEvents($uuid);

        if ($events === []) {
            return null;
        }

        $project = Project::createEmpty();

        foreach ($events as $event) {
            $project->replayEvent($event);
        }

        return $project;
    }

    public function exists(Uuid $uuid): bool
    {
        $events = $this->eventStore->getEvents($uuid);
        return $events !== [];
    }
}
