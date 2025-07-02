<?php

declare(strict_types=1);

namespace App\Project\Domain\Exception;

use App\Shared\ValueObject\Uuid;

final class WorkerAlreadyExistsException extends \DomainException
{
    public function __construct(Uuid $projectId, Uuid $userId)
    {
        parent::__construct("Worker with user id {$userId} already exists in project {$projectId}");
    }
}