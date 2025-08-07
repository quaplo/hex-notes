<?php

declare(strict_types=1);

namespace App\User\Application\Exception;

use Throwable;

final class UserNotFoundException extends ApplicationException
{
    public function __construct(string $userId, ?Throwable $throwable = null)
    {
        parent::__construct("User ID '$userId' not found.", 0, $throwable);
    }
}
