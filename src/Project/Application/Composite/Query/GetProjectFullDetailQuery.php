<?php

declare(strict_types=1);

namespace App\Project\Application\Composite\Query;

use App\Shared\ValueObject\Uuid;

final readonly class GetProjectFullDetailQuery
{
    private function __construct(
        public Uuid $id
    ) {}

    public static function fromPrimitives(string $id): self
    {
        return new self(Uuid::create($id));
    }
}
