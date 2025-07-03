<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\User\Application\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;

final readonly class DeleteUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function __invoke(DeleteUserCommand $command): void
    {
        $userId = $command->getUserId();
        
        $user = $this->userRepository->findByIdIncludingDeleted($userId);
        if (!$user) {
            throw new UserNotFoundException($userId->toString());
        }
        
        if ($user->isDeleted()) {
            return; // Already deleted, no action needed
        }
        
        // Call domain method to handle business logic and record events
        $user->delete();
        
        // Save the user with updated state
        $this->userRepository->save($user);
        
        // Domain events will be dispatched by the repository or infrastructure layer
    }
}