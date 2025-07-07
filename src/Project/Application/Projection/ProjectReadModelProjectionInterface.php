<?php

declare(strict_types=1);

namespace App\Project\Application\Projection;

use App\Shared\Domain\Event\DomainEvent;

interface ProjectReadModelProjectionInterface
{
    public function handle(DomainEvent $domainEvent): void;
}