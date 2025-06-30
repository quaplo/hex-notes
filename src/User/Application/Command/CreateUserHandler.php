<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\User\Application\UserEventSourcingService;
use App\User\Domain\Model\User;

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