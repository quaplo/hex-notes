<?php

declare(strict_types=1);

namespace App\User\Application\Query;

use App\Infrastructure\Http\Dto\UserDto;
use App\Infrastructure\Http\Mapper\UserDtoMapper;
use App\User\Application\UserEventSourcingService;

final class GetUserByEmailHandler
{
    public function __construct(
        private readonly UserEventSourcingService $userEventSourcingService,
        private readonly UserDtoMapper $mapper
    ) {
    }

    public function __invoke(GetUserByEmailQuery $query): ?UserDto
    {
        $user = $this->userEventSourcingService->getUserByEmail($query->getEmail());

        return $user ? $this->mapper->toDto($user) : null;
    }
}
