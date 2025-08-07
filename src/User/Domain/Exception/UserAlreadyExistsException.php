<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\ValueObject\Email;

final class UserAlreadyExistsException extends DomainException
{
    public function __construct(Email $email)
    {
        parent::__construct(\sprintf('User with email "%s" already exists', $email->__toString()));
    }
}
