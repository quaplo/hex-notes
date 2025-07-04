<?php

declare(strict_types=1);

namespace App\Project\Application\Query;

use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Event\EventStore;
use App\Infrastructure\Http\Mapper\ProjectDtoMapper;

final readonly class GetProjectHistoryHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private EventStore $eventStore,
        private ProjectDtoMapper $projectDtoMapper
    ) {
    }

    public function __invoke(GetProjectHistoryQuery $query): array
    {
        $project = $this->projectRepository->load($query->projectId);

        if (!$project) {
            return [];
        }

        // Get all historical events from event store
        $events = $this->eventStore->getEvents($query->projectId);

        // Convert events to serializable data
        $eventData = array_map(
            fn($event) => [
                'eventName' => $event->getEventName(),
                'data' => $event->getEventData(),
                'occurredAt' => $event->getOccurredAt()->format('Y-m-d H:i:s')
            ],
            $events
        );

        return [
            'project' => $this->projectDtoMapper->toDto($project),
            'events' => $eventData
        ];
    }
}