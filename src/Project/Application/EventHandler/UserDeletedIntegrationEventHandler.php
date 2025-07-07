<?php

declare(strict_types=1);

namespace App\Project\Application\EventHandler;

use App\Shared\Domain\Event\UserDeletedIntegrationEvent;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Project\Application\Command\DeleteOrphanedProjectsCommand;

final readonly class UserDeletedIntegrationEventHandler
{
    public function __construct(
        private MessageBusInterface $messageBus
    ) {
    }

    public function __invoke(UserDeletedIntegrationEvent $userDeletedIntegrationEvent): void
    {
        // Integration Event → Project domain command
        $this->messageBus->dispatch(
            new DeleteOrphanedProjectsCommand($userDeletedIntegrationEvent->getUserId())
        );
    }
}