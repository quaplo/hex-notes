<?php

declare(strict_types=1);

namespace App\Project\Application\ReadModel;

use App\Shared\ValueObject\Uuid;

interface ProjectReadModelRepositoryInterface
{
    public function findByOwnerId(Uuid $uuid): array;

    public function findByOwnerIdIncludingDeleted(Uuid $uuid): array;
}
