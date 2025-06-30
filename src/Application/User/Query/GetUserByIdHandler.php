<?php

declare(strict_types=1);

namespace App\Application\User\Query;

use App\Application\User\UserEventSourcingService;
use App\Infrastructure\Http\Dto\UserDto;
use App\Infrastructure\Http\Mapper\UserDtoMapper;

final class GetUserByIdHandler
{
    public function __construct(
        private readonly UserEventSourcingService $userEventSourcingService,
        private readonly UserDtoMapper $mapper
    ) {
    }

    public function __invoke(GetUserByIdQuery $query): ?UserDto
    {
        $user = $this->userEventSourcingService->getUserById($query->getUserId());
        
        return $user ? $this->mapper->toDto($user) : null;
    }
} 