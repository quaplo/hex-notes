<?php

declare(strict_types=1);

namespace App\User\Application\EventHandler;

use App\Shared\Domain\Event\UserDeletedIntegrationEvent;
use App\Shared\Event\EventDispatcher;
use App\User\Domain\Event\UserDeletedEvent;

final readonly class UserDeletedDomainEventHandler
{
    public function __construct(
        private EventDispatcher $eventDispatcher,
    ) {
    }

    public function __invoke(UserDeletedEvent $userDeletedEvent): void
    {
        // Transform Domain Event → Integration Event
        $userDeletedIntegrationEvent = UserDeletedIntegrationEvent::create(
            $userDeletedEvent->getUserId(),
            $userDeletedEvent->getEmail()->__toString()
        );

        // Publish Integration Event for other domains to consume
        $this->eventDispatcher->dispatch([$userDeletedIntegrationEvent]);
    }
}
