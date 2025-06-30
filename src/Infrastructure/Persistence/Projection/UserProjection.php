<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Projection;

use App\Domain\User\Event\UserCreatedEvent;
use App\Shared\Event\DomainEvent;
use App\Shared\Event\EventDispatcher;
use App\Shared\ValueObject\Email;
use App\Shared\ValueObject\Uuid;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final class UserProjection implements EventDispatcher
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    public function dispatch(array $events): void
    {
        foreach ($events as $event) {
            $this->handleEvent($event);
        }
    }

    private function handleEvent(DomainEvent $event): void
    {
        if ($event instanceof UserCreatedEvent) {
            $this->handleUserCreated($event);
        }
    }

    private function handleUserCreated(UserCreatedEvent $event): void
    {
        $sql = 'INSERT INTO user_projection (id, email, created_at) VALUES (?, ?, ?)';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(1, $event->userId->toString());
        $stmt->bindValue(2, $event->email->__toString());
        $stmt->bindValue(3, $event->createdAt->format('Y-m-d H:i:s'));
        
        $stmt->executeStatement();
    }

    public function emailExists(Email $email): bool
    {
        $sql = 'SELECT COUNT(*) FROM user_projection WHERE email = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(1, $email->__toString());
        $result = $stmt->executeQuery();
        
        return $result->fetchOne() > 0;
    }

    public function getUserById(Uuid $userId): ?array
    {
        $sql = 'SELECT id, email, created_at FROM user_projection WHERE id = ?';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(1, $userId->toString());
        $result = $stmt->executeQuery();
        
        $row = $result->fetchAssociative();
        
        return $row ?: null;
    }
} 