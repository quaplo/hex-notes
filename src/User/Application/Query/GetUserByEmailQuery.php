<?php

declare(strict_types=1);

namespace App\User\Application\Query;

use App\Shared\ValueObject\Email;

final class GetUserByEmailQuery
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
