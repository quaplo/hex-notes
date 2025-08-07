<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Mapper;

use App\Infrastructure\Http\Dto\ProjectDto;
use App\Project\Domain\Model\Project;

interface ProjectDtoMapperInterface
{
    public function toDto(Project $project): ProjectDto;
}
