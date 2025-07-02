<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\ValueObject\Uuid;

final class DeleteUserCommand
{
    public function __construct(
        public readonly string $userId
    ) {
    }

    public function getUserId(): Uuid
    {
        return Uuid::create($this->userId);
    }
}