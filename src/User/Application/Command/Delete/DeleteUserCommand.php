<?php

declare(strict_types=1);

namespace App\User\Application\Command\Delete;

use App\Shared\ValueObject\Uuid;

final readonly class DeleteUserCommand
{
    private function __construct(
        private string $userId,
    ) {
    }

    public static function fromPrimitives(string $userId): self
    {
        return new self($userId);
    }

    public function getUserId(): Uuid
    {
        return Uuid::create($this->userId);
    }
}
