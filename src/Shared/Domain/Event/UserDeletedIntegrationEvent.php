<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use App\Shared\ValueObject\Uuid;

final readonly class UserDeletedIntegrationEvent implements DomainEvent
{
    public function __construct(
        private Uuid $userId,
        private string $userEmail,
        private \DateTimeImmutable $occurredAt
    ) {
    }

    public static function create(Uuid $userId, string $userEmail): self
    {
        return new self($userId, $userEmail, new \DateTimeImmutable());
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }

    public function getUserEmail(): string
    {
        return $this->userEmail;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function toArray(): array
    {
        return [
            'userId' => $this->userId->toString(),
            'userEmail' => $this->userEmail,
            'occurredAt' => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            Uuid::create($data['userId']),
            $data['userEmail'],
            new \DateTimeImmutable($data['occurredAt'])
        );
    }
}