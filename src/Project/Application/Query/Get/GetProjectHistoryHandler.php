<?php

declare(strict_types=1);

namespace App\Project\Application\Query\Get;

use App\Project\Application\Mapper\ProjectDtoMapperInterface;
use App\Project\Domain\Model\Project;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Event\EventStore;

final readonly class GetProjectHistoryHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private EventStore $eventStore,
        private ProjectDtoMapperInterface $projectDtoMapper,
    ) {
    }

    public function __invoke(GetProjectHistoryQuery $getProjectHistoryQuery): array
    {
        $project = $this->projectRepository->load($getProjectHistoryQuery->getProjectId());

        if (!$project instanceof Project) {
            return [];
        }

        // Get all historical events from event store
        $events = $this->eventStore->getEvents($getProjectHistoryQuery->getProjectId());

        // Convert events to serializable data
        $eventData = array_map(
            fn (DomainEvent $event): array => [
                'eventName' => $event->getEventName(),
                'data' => $event->getEventData(),
                'occurredAt' => $event->getOccurredAt()->format('Y-m-d H:i:s'),
            ],
            $events
        );

        return [
            'project' => $this->projectDtoMapper->toDto($project),
            'events' => $eventData,
        ];
    }
}
