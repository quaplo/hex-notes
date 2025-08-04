<?php

declare(strict_types=1);

namespace App\User\Application\Query\Get;

use App\User\Application\Dto\UserDto;
use App\User\Application\Mapper\UserDtoMapperInterface;
use App\User\Domain\Model\User;
use App\User\Domain\Repository\UserRepositoryInterface;

final readonly class GetUserByIdHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserDtoMapperInterface $userDtoMapper
    ) {
    }

    public function __invoke(GetUserByIdQuery $getUserByIdQuery): ?UserDto
    {
        $user = $this->userRepository->findById($getUserByIdQuery->getUserId());

        return $user instanceof User ? $this->userDtoMapper->toDto($user) : null;
    }
}
