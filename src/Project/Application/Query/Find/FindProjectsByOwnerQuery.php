<?php

declare(strict_types=1);

namespace App\Project\Application\Query\Find;

use App\Shared\ValueObject\Uuid;

final readonly class FindProjectsByOwnerQuery
{
    private function __construct(
        private Uuid $ownerId,
    ) {
    }

    public static function fromPrimitives(string $ownerId): self
    {
        return new self(Uuid::create($ownerId));
    }

    public function getOwnerId(): Uuid
    {
        return $this->ownerId;
    }
}
