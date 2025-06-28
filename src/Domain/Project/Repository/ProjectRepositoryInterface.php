<?php

declare(strict_types=1);

namespace App\Domain\Project\Repository;

use App\Domain\Project\Model\Project;

interface ProjectRepositoryInterface
{
    public function save(Project $project): void;

    public function delete(Project $project): void;

    public function findById(string $id): ?Project;

    /**
     * @return Project[]
     */
    public function findAll(): array;
}
