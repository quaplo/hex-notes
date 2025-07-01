<?php

declare(strict_types=1);

namespace App\Project\Application\Composite\Dto;

use App\Infrastructure\Http\Dto\ProjectDto;
use App\Infrastructure\Http\Dto\UserDto;

final readonly class ProjectFullDetailDto
{
    public function __construct(
        public ProjectDto $project,
        public UserDto $owner,
        public array $workers = []
    ) {
    }
}
