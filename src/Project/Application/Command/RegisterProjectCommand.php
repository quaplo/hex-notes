<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

use App\Project\Domain\ValueObject\ProjectName;
use App\Shared\ValueObject\Email;

final class RegisterProjectCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $ownerEmail,
    ) {
    }

    public function getName(): ProjectName
    {
        return new ProjectName($this->name);
    }

    public function getOwnerEmail(): Email
    {
        return new Email($this->ownerEmail);
    }
}
