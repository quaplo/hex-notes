<?php

declare(strict_types=1);

namespace App\Application\Project;

use App\Domain\Project\Model\Project;

final readonly class RegisterProjectHandler
{
    public function __construct(
        private ProjectService $service,
    ) {
    }

    public function handle(string $name, string $ownerEmail): Project
    {
        return $this->service->registerProjectWithOwner($name, $ownerEmail);
    }
}
