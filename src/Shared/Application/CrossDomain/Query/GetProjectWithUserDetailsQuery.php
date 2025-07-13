<?php

declare(strict_types=1);

namespace App\Shared\Application\CrossDomain\Query;

use App\Shared\ValueObject\Uuid;

final readonly class GetProjectWithUserDetailsQuery
{
    public function __construct(
        public Uuid $projectId
    ) {
    }

    public static function fromPrimitives(string $id): self
    {
        return new self(Uuid::create($id));
    }
}
