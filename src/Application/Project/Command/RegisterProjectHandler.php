<?php

declare(strict_types=1);

namespace App\Application\Project\Command;

use App\Application\Project\EventSourcingService;
use App\Domain\Project\Model\Project;

final class RegisterProjectHandler
{
    public function __construct(
        private EventSourcingService $service
    ) {
    }

    public function __invoke(RegisterProjectCommand $command): Project
    {
        return $this->service->createProject($command->name, $command->ownerEmail);
    }
}
