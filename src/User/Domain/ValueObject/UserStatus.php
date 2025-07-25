<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case DELETED = 'deleted';

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isInactive(): bool
    {
        return $this === self::INACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this === self::SUSPENDED;
    }

    public function isDeleted(): bool
    {
        return $this === self::DELETED;
    }

    public function canPerformActions(): bool
    {
        return $this->isActive();
    }
}
