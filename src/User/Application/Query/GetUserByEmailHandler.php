<?php

declare(strict_types=1);

namespace App\User\Application\Query;

use App\User\Application\Dto\UserDto;
use App\User\Application\Mapper\UserDtoMapper;
use App\User\Domain\Model\User;
use App\User\Domain\Repository\UserRepositoryInterface;

final readonly class GetUserByEmailHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserDtoMapper $userDtoMapper
    ) {
    }

    public function __invoke(GetUserByEmailQuery $getUserByEmailQuery): ?UserDto
    {
        $user = $this->userRepository->findByEmail($getUserByEmailQuery->getEmail());

        return $user instanceof User ? $this->userDtoMapper->toDto($user) : null;
    }
}
