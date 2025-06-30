<?php

declare(strict_types=1);

namespace App\Domain\User\Model;

use App\Domain\User\Event\UserCreatedEvent;
use App\Shared\Aggregate\AggregateRoot;
use App\Shared\ValueObject\Email;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final class User extends AggregateRoot
{
    private Uuid $id;
    private Email $email;
    private DateTimeImmutable $createdAt;

    private function __construct()
    {
    }

    public static function create(Email $email): self
    {
        $user = new self();
        $user->id = Uuid::generate();
        $user->email = $email;
        $user->createdAt = new DateTimeImmutable();

        $user->recordEvent(new UserCreatedEvent(
            $user->id,
            $user->email,
            $user->createdAt
        ));

        return $user;
    }

    public static function fromEvents(array $events): self
    {
        $user = new self();
        
        foreach ($events as $event) {
            $user->replayEvent($event);
        }

        return $user;
    }

    protected function handleEvent(\App\Shared\Event\DomainEvent $event): void
    {
        if ($event instanceof UserCreatedEvent) {
            $this->id = $event->userId;
            $this->email = $event->email;
            $this->createdAt = $event->createdAt;
        }
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
