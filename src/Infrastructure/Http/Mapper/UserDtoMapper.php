<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Mapper;

use App\Domain\User\Model\User;
use App\Infrastructure\Http\Dto\UserDto;

final class UserDtoMapper
{
    public function toDto(User $user): UserDto
    {
        return new UserDto(
            id: $user->getId()->toString(),
            email: $user->getEmail()->__toString(),
            createdAt: $user->getCreatedAt()->format('Y-m-d H:i:s')
        );
    }
} 