<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Model\User;
use App\User\Domain\Repository\UserRepositoryInterface;

final readonly class CreateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function __invoke(CreateUserCommand $createUserCommand): User
    {
        $email = $createUserCommand->getEmail();
        
        $existingUser = $this->userRepository->findByEmailIncludingDeleted($email);
        if ($existingUser instanceof User) {
            throw new UserAlreadyExistsException($email);
        }
        
        $user = User::register($email);
        $this->userRepository->save($user);
        
        return $user;
    }
}
