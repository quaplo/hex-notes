<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\User\UserEventSourcingService;
use App\Domain\User\Model\User;

final class CreateUserHandler
{
    public function __construct(
        private UserEventSourcingService $userEventSourcingService
    ) {
    }

    public function __invoke(CreateUserCommand $command): User
    {
        return $this->userEventSourcingService->createUser($command->getEmail());
    }
} 