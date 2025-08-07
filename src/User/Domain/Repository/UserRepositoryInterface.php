<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\Shared\ValueObject\Email;
use App\Shared\ValueObject\Uuid;
use App\User\Domain\Model\User;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function delete(Uuid $uuid): void;

    public function findByEmail(Email $email): ?User;

    public function findById(Uuid $uuid): ?User;

    public function findByEmailIncludingDeleted(Email $email): ?User;

    public function findByIdIncludingDeleted(Uuid $uuid): ?User;
}
