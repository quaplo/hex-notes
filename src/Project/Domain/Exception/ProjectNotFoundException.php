<?php

declare(strict_types=1);

namespace App\Project\Domain\Exception;

use DomainException;
use App\Shared\ValueObject\Uuid;

final class ProjectNotFoundException extends DomainException
{
    public function __construct(Uuid $uuid)
    {
        parent::__construct("Project with id {$uuid} not found");
    }
}