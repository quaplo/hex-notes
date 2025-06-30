<?php

declare(strict_types=1);

namespace App\Project\Application\Composite\Query;

use App\Shared\ValueObject\Uuid;

final class GetProjectFullDetailQuery
{
    public function __construct(
        public readonly string $id
    ) {
    }

    public function getId(): Uuid
    {
        return Uuid::create($this->id);
    }
}
