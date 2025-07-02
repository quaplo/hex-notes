<?php

declare(strict_types=1);

namespace App\User\Application\Query;

use App\Infrastructure\Http\Dto\UserDto;
use App\Infrastructure\Http\Mapper\UserDtoMapper;
use App\User\Domain\Repository\UserRepositoryInterface;

final readonly class GetUserByIdHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserDtoMapper $mapper
    ) {
    }

    public function __invoke(GetUserByIdQuery $query): ?UserDto
    {
        $user = $this->userRepository->findById($query->getUserId());

        return $user ? $this->mapper->toDto($user) : null;
    }
}
