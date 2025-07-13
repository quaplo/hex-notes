<?php

namespace App\Infrastructure\Http\Mapper;

use App\Infrastructure\Http\Dto\ProjectDto;
use App\Project\Domain\Model\Project;

interface ProjectDtoMapperInterface
{
    public function toDto(Project $project): ProjectDto;
}
