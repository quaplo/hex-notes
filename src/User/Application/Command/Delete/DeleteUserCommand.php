<?php

declare(strict_types=1);

namespace App\User\Application\Command\Delete;

use App\Shared\ValueObject\Uuid;

final readonly class DeleteUserCommand
{
    public function __construct(
        public string $userId
    ) {
    }

    public function getUserId(): Uuid
    {
        return Uuid::create($this->userId);
    }
}
