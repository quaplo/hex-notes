<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\User\Application\UserService;
use App\User\Domain\Model\User;

final class CreateUserHandler
{
    public function __construct(
        private UserService $userService
    ) {
    }

    public function __invoke(CreateUserCommand $command): User
    {
        return $this->userService->createUser($command->getEmail());
    }
}
