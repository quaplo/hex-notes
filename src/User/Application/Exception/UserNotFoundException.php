<?php

declare(strict_types=1);

namespace App\User\Application\Exception;

use Throwable;

class UserNotFoundException extends ApplicationException
{
    public function __construct(string $userId, Throwable $previous = null)
    {
        parent::__construct("User ID '$userId' exist.", 0, $previous);
    }
}
