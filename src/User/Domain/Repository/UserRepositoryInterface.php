<?php

namespace App\User\Domain\Repository;

use App\Shared\ValueObject\Email;
use App\Shared\ValueObject\Uuid;
use App\User\Domain\Model\User;

interface UserRepositoryInterface
{
    public function save(User $user): void;
    public function delete(Uuid $userId): void;

    public function findByEmail(Email $email): ?User;
    public function findById(Uuid $userId): ?User;
    
    public function findByEmailIncludingDeleted(Email $email): ?User;
    public function findByIdIncludingDeleted(Uuid $userId): ?User;
}
