<?php

declare(strict_types=1);

namespace App\Project\Domain\Exception;

use App\Shared\ValueObject\Uuid;

final class ProjectNotFoundException extends \DomainException
{
    public function __construct(Uuid $projectId)
    {
        parent::__construct("Project with id {$projectId} not found");
    }
}