<?php

declare(strict_types=1);

namespace App\Project\Application\Query;

use App\Shared\ValueObject\Uuid;

final readonly class GetProjectQuery
{
    public function __construct(
        public string $id
    ) {
    }

    public function getId(): Uuid
    {
        return Uuid::create($this->id);
    }
}
