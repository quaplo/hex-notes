<?php

declare(strict_types=1);

namespace App\User\Application\Query\Get;

use App\Shared\ValueObject\Email;

final readonly class GetUserByEmailQuery
{
    public function __construct(
        public string $email
    ) {
    }

    public function getEmail(): Email
    {
        return new Email($this->email);
    }
}
