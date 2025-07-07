<?php

declare(strict_types=1);

namespace App\Shared\Application\CrossDomain\Dto;

use App\Shared\Application\Dto\ProjectDto;
use App\User\Application\Dto\UserDto;

final readonly class ProjectWithUserDetailsDto
{
    public function __construct(
        public ProjectDto $project,
        public ?UserDto $owner,
        public array $workers = []
    ) {
    }
}