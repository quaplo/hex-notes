<?php

declare(strict_types=1);

namespace App\Infrastructure\Event;

use App\Infrastructure\Persistence\Projection\UserProjection;
use App\Project\Application\EventHandler\ProjectEventHandler;
use App\Shared\Event\DomainEvent;
use App\Shared\Event\EventDispatcher;
use Psr\Log\LoggerInterface;

final class SymfonyEventDispatcher implements EventDispatcher
{
    public function __construct(
        private readonly ProjectEventHandler $projectEventHandler,
        private readonly UserProjection $userProjection,
        private readonly LoggerInterface $logger
    ) {
    }

    public function dispatch(array $events): void
    {
        foreach ($events as $event) {
            try {
                $this->dispatchEvent($event);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to dispatch event', [
                    'event' => get_class($event),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // In a production system, you might want to:
                // - Store failed events for retry
                // - Send alerts
                // - Rollback transaction
                throw $e;
            }
        }
    }

    private function dispatchEvent(DomainEvent $event): void
    {
        // Route events to appropriate handlers
        match (get_class($event)) {
            \App\Project\Domain\Event\ProjectCreatedEvent::class,
            \App\Project\Domain\Event\ProjectRenamedEvent::class,
            \App\Project\Domain\Event\ProjectDeletedEvent::class => $this->projectEventHandler->handle($event),
            \App\User\Domain\Event\UserCreatedEvent::class => $this->userProjection->dispatch([$event]),
            default => $this->logger->warning('No handler found for event', ['event' => get_class($event)])
        };
    }
} 