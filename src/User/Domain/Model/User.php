<?php

declare(strict_types=1);

namespace App\User\Domain\Model;

use RuntimeException;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\ValueObject\Email;
use App\Shared\ValueObject\Uuid;
use App\User\Domain\Event\UserDeletedEvent;
use App\User\Domain\ValueObject\UserStatus;
use App\User\Domain\Exception\UserInactiveException;
use DateTimeImmutable;

final class User extends AggregateRoot
{
    private function __construct(
        private readonly Uuid $uuid,
        private Email $email,
        private UserStatus $userStatus,
        private readonly DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $deletedAt = null
    ) {
    }

    public static function fromPrimitives(
        string $id,
        string $email,
        string $status,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $deletedAt = null
    ): self {
        return new self(
            Uuid::create($id),
            Email::fromString($email),
            UserStatus::from($status),
            $createdAt,
            $deletedAt
        );
    }

    public static function register(Email $email): self
    {
        return new self(
            Uuid::generate(),
            $email,
            UserStatus::ACTIVE,
            new DateTimeImmutable()
        );
    }

    public function changeEmail(Email $newEmail): void
    {
        if ($this->email->equals($newEmail)) {
            return; // No change needed
        }

        if (!$this->canChangeEmail()) {
            throw new UserInactiveException($this->uuid);
        }

        $this->email = $newEmail;
    }

    public function activate(): void
    {
        $this->userStatus = UserStatus::ACTIVE;
    }

    public function deactivate(): void
    {
        $this->userStatus = UserStatus::INACTIVE;
    }

    public function suspend(): void
    {
        $this->userStatus = UserStatus::SUSPENDED;
    }

    public function delete(): void
    {
        if ($this->isDeleted()) {
            return; // Already deleted - idempotent operation
        }
        
        // Record domain event for cross-domain communication
        $this->apply(UserDeletedEvent::create($this->uuid, $this->email));
    }

    public function isActive(): bool
    {
        return $this->userStatus->isActive();
    }

    public function isInactive(): bool
    {
        return $this->userStatus->isInactive();
    }

    public function isSuspended(): bool
    {
        return $this->userStatus->isSuspended();
    }

    public function isDeleted(): bool
    {
        return $this->userStatus->isDeleted();
    }

    public function canChangeEmail(): bool
    {
        return $this->userStatus->canPerformActions();
    }

    public function canPerformActions(): bool
    {
        return $this->userStatus->canPerformActions();
    }

    // Getters
    public function getId(): Uuid
    {
        return $this->uuid;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getStatus(): UserStatus
    {
        return $this->userStatus;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    /**
     * Implementation of abstract handleEvent method from AggregateRoot
     */
    protected function handleEvent(DomainEvent $domainEvent): void
    {
        match ($domainEvent::class) {
            UserDeletedEvent::class => $this->handleUserDeleted($domainEvent),
            default => throw new RuntimeException('Unknown event type: ' . $domainEvent::class)
        };
    }

    private function handleUserDeleted(UserDeletedEvent $userDeletedEvent): void
    {
        $this->userStatus = UserStatus::DELETED;
        $this->deletedAt = $userDeletedEvent->getOccurredAt();
    }
}
