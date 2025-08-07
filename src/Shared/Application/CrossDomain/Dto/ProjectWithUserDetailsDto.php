<?php

declare(strict_types=1);

namespace App\Shared\Application\CrossDomain\Dto;

use App\User\Application\Dto\UserDto;

final readonly class ProjectWithUserDetailsDto
{
    public function __construct(
        public array $project,
    ) {
    }

    public static function create(
        string $id,
        string $name,
        UserDto $userDto,
        array $workers,
        bool $isDeleted,
    ): self {
        return new self([
            'id' => $id,
            'name' => $name,
            'owner' => $userDto,
            'workers' => $workers,
            'isDeleted' => $isDeleted,
        ]);
    }
}
