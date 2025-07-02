<?php

declare(strict_types=1);

namespace App\Infrastructure\Bus;

use App\Shared\Application\CommandBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class SymfonyCommandBus implements CommandBus
{
    public function __construct(
        private readonly MessageBusInterface $commandBus
    ) {}

    public function dispatch(object $command): mixed
    {
        $envelope = $this->commandBus->dispatch($command);
        
        /** @var HandledStamp|null $handledStamp */
        $handledStamp = $envelope->last(HandledStamp::class);
        
        return $handledStamp?->getResult();
    }
}