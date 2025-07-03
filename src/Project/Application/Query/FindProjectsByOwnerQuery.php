<?php

declare(strict_types=1);

namespace App\Project\Application\Query;

use App\Shared\ValueObject\Uuid;

final readonly class FindProjectsByOwnerQuery
{
    public function __construct(
        public readonly Uuid $ownerId
    ) {
    }

    public function getOwnerId(): Uuid
    {
        return $this->ownerId;
    }
}