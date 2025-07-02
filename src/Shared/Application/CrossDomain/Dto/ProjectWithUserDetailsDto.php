<?php

declare(strict_types=1);

namespace App\Shared\Application\CrossDomain\Dto;

use App\Infrastructure\Http\Dto\ProjectDto;
use App\Infrastructure\Http\Dto\UserDto;

final readonly class ProjectWithUserDetailsDto
{
    public function __construct(
        public ProjectDto $project,
        public ?UserDto $owner,
        public array $workers = []
    ) {
    }
}