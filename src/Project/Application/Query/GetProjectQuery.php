<?php

declare(strict_types=1);

namespace App\Project\Application\Query;

use App\Shared\ValueObject\Uuid;

final class GetProjectQuery
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
