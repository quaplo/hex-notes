<?php

declare(strict_types=1);

namespace App\Shared\Application;

interface CommandBus
{
    /**
     * Dispatches a command to its handler and returns the result.
     */
    public function dispatch(object $command): mixed;
}
