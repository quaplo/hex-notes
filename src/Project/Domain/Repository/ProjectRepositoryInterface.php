<?php

declare(strict_types=1);

namespace App\Project\Domain\Repository;

use App\Project\Domain\Model\Project;
use App\Shared\ValueObject\Uuid;

interface ProjectRepositoryInterface
{
    public function save(Project $project): void;
    
    public function load(Uuid $uuid): ?Project;
    
    public function exists(Uuid $uuid): bool;
}