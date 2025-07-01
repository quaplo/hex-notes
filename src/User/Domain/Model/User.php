<?php

declare(strict_types=1);

namespace App\User\Domain\Model;

use App\Shared\ValueObject\Email;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final readonly class User
{
    private function __construct(
        private Uuid $id,
        private Email $email,
        private DateTimeImmutable $createdAt
    ) {
    }

    public static function fromPrimitives(string $id, string $email, DateTimeImmutable $createdAt): self
    {
        return new self(
            Uuid::create($id),
            Email::fromString($email),
            $createdAt
        );
    }
    public static function create(Email $email): self
    {
        return new self(
            Uuid::generate(),
            $email,
            new DateTimeImmutable()
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
