<?php

declare(strict_types=1);

namespace App\User\Application\Command\Create;

use App\Shared\ValueObject\Email;

final readonly class CreateUserCommand
{
    public function __construct(
        public string $email,
    ) {
    }

    public function getEmail(): Email
    {
        return new Email($this->email);
    }
}
