<?php

declare(strict_types=1);

namespace App\Project\Application\Mapper;

use App\Project\Domain\Model\Project;

interface ProjectDtoMapperInterface
{
    public function toDto(Project $project): array;
}