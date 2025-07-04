<?php

declare(strict_types=1);

namespace App\User\Domain\Model;

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
        private readonly Uuid $id,
        private Email $email,
        private UserStatus $status,
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
            throw new UserInactiveException($this->id);
        }

        $this->email = $newEmail;
    }

    public function activate(): void
    {
        $this->status = UserStatus::ACTIVE;
    }

    public function deactivate(): void
    {
        $this->status = UserStatus::INACTIVE;
    }

    public function suspend(): void
    {
        $this->status = UserStatus::SUSPENDED;
    }

    public function delete(): void
    {
        if ($this->isDeleted()) {
            return; // Already deleted - idempotent operation
        }
        
        // Record domain event for cross-domain communication
        $this->apply(UserDeletedEvent::create($this->id, $this->email));
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isInactive(): bool
    {
        return $this->status->isInactive();
    }

    public function isSuspended(): bool
    {
        return $this->status->isSuspended();
    }

    public function isDeleted(): bool
    {
        return $this->status->isDeleted();
    }

    public function canChangeEmail(): bool
    {
        return $this->status->canPerformActions();
    }

    public function canPerformActions(): bool
    {
        return $this->status->canPerformActions();
    }

    // Getters
    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getStatus(): UserStatus
    {
        return $this->status;
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
    protected function handleEvent(DomainEvent $event): void
    {
        match (get_class($event)) {
            UserDeletedEvent::class => $this->handleUserDeleted($event),
            default => throw new \RuntimeException('Unknown event type: ' . get_class($event))
        };
    }

    private function handleUserDeleted(UserDeletedEvent $event): void
    {
        $this->status = UserStatus::DELETED;
        $this->deletedAt = $event->getOccurredAt();
    }
}
