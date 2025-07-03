<?php

declare(strict_types=1);

namespace App\Shared\Application\EventHandler;

use App\User\Domain\Event\UserDeletedEvent;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Project\Application\Command\DeleteOrphanedProjectsCommand;

final readonly class UserDeletedEventHandler
{
    public function __construct(
        private MessageBusInterface $commandBus
    ) {
    }

    public function __invoke(UserDeletedEvent $event): void
    {
        // Dispatch command to clean up orphaned projects
        $this->commandBus->dispatch(
            new DeleteOrphanedProjectsCommand($event->getUserId())
        );
    }
}