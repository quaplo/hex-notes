<?php

declare(strict_types=1);

namespace App\Project\Application\Query;

use App\Shared\ValueObject\Uuid;

final readonly class FindProjectsForDeletionQuery
{
    public function __construct(
        public Uuid $ownerId
    ) {
    }
}
