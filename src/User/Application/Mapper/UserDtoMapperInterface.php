<?php

declare(strict_types=1);

namespace App\User\Application\Mapper;

use App\User\Application\Dto\UserDto;
use App\User\Domain\Model\User;

interface UserDtoMapperInterface
{
    public function toDto(User $user): UserDto;
}
