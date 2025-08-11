<?php

declare(strict_types=1);

namespace App\User\Application\EventHandler;

use App\Shared\Domain\Event\UserDeletedIntegrationEvent;
use App\User\Domain\Event\UserDeletedEvent;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class UserDeletedDomainEventHandler
{
    public function __construct(
        private MessageBusInterface $eventBus,
    ) {
    }

    public function __invoke(UserDeletedEvent $userDeletedEvent): void
    {
        // Transform Domain Event → Integration Event
        $userDeletedIntegrationEvent = UserDeletedIntegrationEvent::create(
            $userDeletedEvent->getUserId(),
            $userDeletedEvent->getEmail()->__toString()
        );

        // Publish Integration Event asynchronously via RabbitMQ
        $this->eventBus->dispatch($userDeletedIntegrationEvent);
    }
}
