<?php

declare(strict_types=1);

namespace App\User\Application;

use App\User\Domain\Model\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use App\Shared\ValueObject\Email;
use App\Shared\ValueObject\Uuid;

final class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function createUser(Email $email): User
    {
        $user = User::create($email);
        $this->userRepository->save($user);
        return $user;
    }

    public function getUserById(Uuid $userId): ?User
    {
        return $this->userRepository->findById($userId);
    }

    public function getUserByEmail(Email $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }
}
