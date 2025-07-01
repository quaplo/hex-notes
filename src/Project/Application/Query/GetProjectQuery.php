<?php

declare(strict_types=1);

namespace App\Project\Application\Query;

use App\Shared\ValueObject\Uuid;

final readonly class GetProjectQuery
{
    private function __construct(
        public Uuid $id
    ) {
    }

    public static function fromPrimitives(string $id): self
    {
        return new self(Uuid::create($id));
    }
}
