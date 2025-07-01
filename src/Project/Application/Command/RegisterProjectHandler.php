<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

use App\Project\Application\ProjectService;
use App\Project\Domain\Model\Project;
use App\Shared\ValueObject\Uuid;

final class RegisterProjectHandler
{
    public function __construct(
        private ProjectService $service
    ) {
    }

    public function __invoke(RegisterProjectCommand $command): Project
    {
        return $this->service->createProject($command->name, $command->ownerId);
    }
}
