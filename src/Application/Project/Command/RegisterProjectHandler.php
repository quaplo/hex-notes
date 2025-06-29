<?php

declare(strict_types=1);

namespace App\Application\Project\Command;

use App\Application\Project\ProjectService;
use App\Domain\Project\Model\Project;

final class RegisterProjectHandler
{
    public function __construct(
        private ProjectService $service
    ) {
    }

    public function __invoke(RegisterProjectCommand $command): Project
    {
        return $this->service->registerProjectWithOwner($command->getName(), $command->getOwnerEmail());
    }
}
