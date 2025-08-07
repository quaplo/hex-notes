<?php

declare(strict_types=1);

namespace App\Project\Application\Command\Register;

use App\Project\Domain\ValueObject\ProjectName;
use App\Shared\ValueObject\Uuid;

final readonly class RegisterProjectCommand
{
    private function __construct(
        public ProjectName $name,
        public Uuid $ownerId,
    ) {
    }

    public static function fromPrimitives(string $name, string $ownerId): self
    {
        return new self(new ProjectName($name), Uuid::create($ownerId));
    }
}
