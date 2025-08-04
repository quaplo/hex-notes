<?php

declare(strict_types=1);

namespace App\User\Application\Query\Get;

use App\Shared\ValueObject\Uuid;

final readonly class GetUserByIdQuery
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
