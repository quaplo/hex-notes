<?php

declare(strict_types=1);

namespace App\User\Application\Mapper;

use App\User\Application\Dto\UserDto;
use App\User\Domain\Model\User;
use App\User\Application\Mapper\UserDtoMapperInterface;

final readonly class UserDtoMapper implements UserDtoMapperInterface
{
    public function toDto(User $user): UserDto
    {
        return new UserDto(
            id: $user->getId()->toString(),
            email: $user->getEmail()->__toString(),
            isDeleted: $user->isDeleted()
        );
    }
}
