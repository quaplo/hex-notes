<?php

declare(strict_types=1);

namespace App\Shared\Application;

interface QueryBus
{
    /**
     * Dispatches a query to its handler and returns the result.
     */
    public function dispatch(object $query): mixed;
}
