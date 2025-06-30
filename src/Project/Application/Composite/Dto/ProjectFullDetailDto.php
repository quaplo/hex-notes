<?php

declare(strict_types=1);

namespace App\Project\Application\Composite\Dto;

use App\Infrastructure\Http\Dto\ProjectDto;
use App\Infrastructure\Http\Dto\UserDto;

final class ProjectFullDetailDto
{
    public function __construct(
        public readonly ProjectDto $project,
        public readonly UserDto $owner,
        public readonly array $workers = []
    ) {
    }
}
