<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\ValueObject\Uuid;

final class UserNotFoundException extends DomainException
{
    public function __construct(Uuid $uuid)
    {
        parent::__construct(sprintf('User with id "%s" not found', $uuid->toString()));
    }
}
