<?php

declare(strict_types=1);

namespace App\Domain\Project\ValueObject;

use App\Shared\ValueObject\Email;

final class ProjectOwner
{
    public function __construct(
        private UserId $id,
        private Email $email,
    ) {
    }

    public static function create(UserId $id, Email $email): self
    {
        return new self($id, $email);
    }

    public function getId(): UserId
    {
        return $this->id;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }
}
