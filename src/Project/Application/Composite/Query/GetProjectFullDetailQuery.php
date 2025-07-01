<?php

declare(strict_types=1);

namespace App\Project\Application\Composite\Query;

use App\Shared\ValueObject\Uuid;

final readonly class GetProjectFullDetailQuery
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
