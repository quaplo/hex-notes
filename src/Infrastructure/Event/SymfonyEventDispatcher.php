<?php

declare(strict_types=1);

namespace App\Infrastructure\Event;

use App\Application\Project\EventHandler\ProjectEventHandler;
use App\Shared\Event\DomainEvent;
use App\Shared\Event\EventDispatcher;
use Psr\Log\LoggerInterface;

final class SymfonyEventDispatcher implements EventDispatcher
{
    public function __construct(
        private readonly ProjectEventHandler $projectEventHandler,
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
            \App\Domain\Project\Event\ProjectCreatedEvent::class,
            \App\Domain\Project\Event\ProjectRenamedEvent::class,
            \App\Domain\Project\Event\ProjectDeletedEvent::class => $this->projectEventHandler->handle($event),
            default => $this->logger->warning('No handler found for event', ['event' => get_class($event)])
        };
    }
} 