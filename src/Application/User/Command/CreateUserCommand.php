<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Shared\ValueObject\Email;

final class CreateUserCommand
{
    public function __construct(
        public readonly string $email
    ) {
    }

    public function getEmail(): Email
    {
        return new Email($this->email);
    }
} 