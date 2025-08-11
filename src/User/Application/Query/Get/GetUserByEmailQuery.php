<?php

declare(strict_types=1);

namespace App\User\Application\Query\Get;

use App\Shared\ValueObject\Email;

final readonly class GetUserByEmailQuery
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
