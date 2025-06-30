<?php

declare(strict_types=1);

namespace App\Application\User;

use App\Domain\User\Event\UserCreatedEvent;
use App\Domain\User\Model\User;
use App\Shared\Event\EventStore;
use App\Shared\Event\EventDispatcher;
use App\Shared\ValueObject\Email;
use App\Shared\ValueObject\Uuid;
use App\Infrastructure\Persistence\Projection\UserProjection;

final class UserEventSourcingService
{
    public function __construct(
        private EventStore $eventStore,
        private EventDispatcher $eventDispatcher,
        private UserProjection $userProjection
    ) {
    }

    public function createUser(Email $email): User
    {
        if ($this->userProjection->emailExists($email)) {
            throw new \RuntimeException('User with this email already exists.');
        }
        $user = User::create($email);
        
        $events = $user->getDomainEvents();
        $this->eventStore->append($user->getId(), $events, $user->getVersion());
        
        $this->eventDispatcher->dispatch($events);

        return $user;
    }

    public function getUserById(Uuid $userId): ?User
    {
        $events = $this->eventStore->getEvents($userId);
        
        if (empty($events)) {
            return null;
        }

        return User::fromEvents($events);
    }

    public function getUserByEmail(Email $email): ?User
    {
        $userId = $this->userProjection->findUserIdByEmail($email);

        if (!$userId) {
            return null;
        }
        
        return $this->getUserById($userId);
    }
} 