<?php

declare(strict_types=1);

namespace App\Domain\Project\Repository;

use App\Domain\Project\Model\Project;
use App\Shared\ValueObject\Uuid;

interface ProjectRepositoryInterface
{
    public function save(Project $project): void;

    public function delete(Project $project): void;

    public function findById(Uuid $id): ?Project;

    /**
     * @return Project[]
     */
    public function findAll(): array;
}
