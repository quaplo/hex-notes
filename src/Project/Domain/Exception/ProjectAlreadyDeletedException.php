<?php

declare(strict_types=1);

namespace App\Project\Domain\Exception;

use App\Shared\ValueObject\Uuid;
use DomainException;

final class ProjectAlreadyDeletedException extends DomainException
{
    public function __construct(Uuid $uuid)
    {
        parent::__construct("Project with id {$uuid} is already deleted");
    }
}
