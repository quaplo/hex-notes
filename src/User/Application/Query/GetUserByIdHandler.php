<?php

declare(strict_types=1);

namespace App\User\Application\Query;

use App\User\Domain\Model\User;
use App\Infrastructure\Http\Dto\UserDto;
use App\Infrastructure\Http\Mapper\UserDtoMapper;
use App\User\Domain\Repository\UserRepositoryInterface;

final readonly class GetUserByIdHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserDtoMapper $userDtoMapper
    ) {
    }

    public function __invoke(GetUserByIdQuery $getUserByIdQuery): ?UserDto
    {
        $user = $this->userRepository->findById($getUserByIdQuery->getUserId());

        return $user instanceof User ? $this->userDtoMapper->toDto($user) : null;
    }
}
