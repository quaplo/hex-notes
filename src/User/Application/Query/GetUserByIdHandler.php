<?php

declare(strict_types=1);

namespace App\User\Application\Query;

use App\Infrastructure\Http\Dto\UserDto;
use App\Infrastructure\Http\Mapper\UserDtoMapper;
use App\User\Application\UserService;

final class GetUserByIdHandler
{
    public function __construct(
        private readonly UserService $userEventSourcingService,
        private readonly UserDtoMapper $mapper
    ) {
    }

    public function __invoke(GetUserByIdQuery $query): ?UserDto
    {
        $user = $this->userEventSourcingService->getUserById($query->getUserId());

        return $user ? $this->mapper->toDto($user) : null;
    }
}
