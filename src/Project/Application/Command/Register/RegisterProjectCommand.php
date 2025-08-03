<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

use App\Shared\ValueObject\Uuid;
use App\Project\Domain\ValueObject\ProjectName;

final readonly class RegisterProjectCommand
{
    private function __construct(
        public ProjectName $name,
        public Uuid $ownerId
    ) {
    }

    public static function fromPrimitives(string $name, string $ownerId): self
    {
        return new self(new ProjectName($name), Uuid::create($ownerId));
    }
}
