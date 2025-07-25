<?php

declare(strict_types=1);

namespace App\User\Application\Exception;

use Throwable;

class EmailAlreadyExistsException extends ApplicationException
{
    public function __construct(string $email, Throwable $throwable = null)
    {
        parent::__construct("Email '$email' exist.", 0, $throwable);
    }
}
