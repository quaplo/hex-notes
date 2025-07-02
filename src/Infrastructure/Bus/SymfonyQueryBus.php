<?php

declare(strict_types=1);

namespace App\Infrastructure\Bus;

use App\Shared\Application\QueryBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class SymfonyQueryBus implements QueryBus
{
    public function __construct(
        private readonly MessageBusInterface $queryBus
    ) {}

    public function dispatch(object $query): mixed
    {
        $envelope = $this->queryBus->dispatch($query);
        
        /** @var HandledStamp|null $handledStamp */
        $handledStamp = $envelope->last(HandledStamp::class);
        
        return $handledStamp?->getResult();
    }
}