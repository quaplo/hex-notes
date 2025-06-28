<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\Model\User;
use App\Shared\ValueObject\Email;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function findByEmail(Email $email): ?User;
}
