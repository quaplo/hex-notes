<?php

declare(strict_types=1);

namespace App\Project\Application\EventHandler;

use App\Project\Application\Command\Delete\DeleteOrphanedProjectsCommand;
use App\Shared\Application\CommandBus;
use App\Shared\Domain\Event\UserDeletedIntegrationEvent;

final readonly class UserDeletedIntegrationEventHandler
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    public function __invoke(UserDeletedIntegrationEvent $userDeletedIntegrationEvent): void
    {
        // Integration Event → Project domain command
        $this->commandBus->dispatch(
            DeleteOrphanedProjectsCommand::fromPrimitives($userDeletedIntegrationEvent->getUserId()->toString())
        );
    }
}
