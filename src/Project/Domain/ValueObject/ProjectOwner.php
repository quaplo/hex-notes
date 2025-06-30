<?php

declare(strict_types=1);

namespace App\Project\Domain\ValueObject;

use App\Shared\ValueObject\Uuid;
use App\Shared\ValueObject\Email;

final class ProjectOwner
{
    public function __construct(
        private Uuid $id,
        private Email $email,
    ) {
    }

    public static function create(Uuid $id, Email $email): self
    {
        return new self($id, $email);
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }
}
