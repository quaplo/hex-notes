<?php

declare(strict_types=1);

namespace App\User\Application\Command\Create;

use App\Shared\ValueObject\Email;

final readonly class CreateUserCommand
{
    private function __construct(
        private string $email,
    ) {
    }

    public static function fromPrimitives(string $email): self
    {
        return new self($email);
    }

    public function getEmail(): Email
    {
        return new Email($this->email);
    }
}
