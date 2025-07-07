<?php

declare(strict_types=1);

namespace App\Shared\Application\Mapper;

use App\Project\Domain\Model\Project;
use App\Shared\Application\Dto\ProjectDto;

interface ProjectDtoMapperInterface
{
    public function toDto(Project $project): ProjectDto;
}