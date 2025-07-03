<?php

namespace App\User\Infrastructure\Persistence\Doctrine;

use App\Shared\ValueObject\Uuid;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Model\User;
use App\User\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use App\Shared\ValueObject\Email;
use App\Shared\Infrastructure\Event\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;

final class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private DomainEventDispatcher $eventDispatcher
    ) {
    }

    public function save(User $user): void
    {
        $existingEntity = $this->em->getRepository(UserEntity::class)->findOneBy(['id' => $user->getId()->toString()]);
        
        if ($existingEntity) {
            // Update existing entity
            $existingEntity->setEmail($user->getEmail()->__toString());
            $existingEntity->setStatus($user->getStatus()->value);
            $existingEntity->setDeletedAt($user->getDeletedAt());
        } else {
            // Create new entity
            $entity = new UserEntity(
                $user->getId()->toString(),
                $user->getEmail()->__toString(),
                $user->getStatus()->value,
                $user->getCreatedAt(),
                $user->getDeletedAt()
            );
            $this->em->persist($entity);
        }
        
        $this->em->flush();
        
        // Dispatch domain events after successful persistence
        if ($user->hasUncommittedEvents()) {
            $this->eventDispatcher->dispatch($user->getUncommittedEvents());
            $user->markEventsAsCommitted();
        }
    }

    public function delete(Uuid $userId): void
    {
        $entity = $this->em->getRepository(UserEntity::class)->findOneBy(['id' => $userId->toString()]);
        
        if (!$entity) {
            return; // User not found, nothing to delete
        }

        if ($entity->getDeletedAt() !== null) {
            return; // Already soft deleted
        }

        $user = $this->mapToDomain($entity);
        $user->delete();
        $this->save($user);
    }

    public function findById(Uuid $id): ?User
    {
        $entity = $this->em->getRepository(UserEntity::class)->findOneBy([
            'id' => $id->__toString(),
            'deletedAt' => null
        ]);
        if (!$entity) {
            return null;
        }
        return $this->mapToDomain($entity);
    }

    public function findByEmail(Email $email): ?User
    {
        $entity = $this->em->getRepository(UserEntity::class)->findOneBy([
            'email' => $email->__toString(),
            'deletedAt' => null
        ]);
        if (!$entity) {
            return null;
        }
        return $this->mapToDomain($entity);
    }

    public function findByIdIncludingDeleted(Uuid $id): ?User
    {
        $entity = $this->em->getRepository(UserEntity::class)->findOneBy(['id' => $id->__toString()]);
        if (!$entity) {
            return null;
        }
        return $this->mapToDomain($entity);
    }

    public function findByEmailIncludingDeleted(Email $email): ?User
    {
        $entity = $this->em->getRepository(UserEntity::class)->findOneBy(['email' => $email->__toString()]);
        if (!$entity) {
            return null;
        }
        return $this->mapToDomain($entity);
    }

    private function mapToDomain(UserEntity $entity): User
    {
        return User::fromPrimitives(
            $entity->getId(),
            $entity->getEmail(),
            $entity->getStatus(),
            $entity->getCreatedAt(),
            $entity->getDeletedAt()
        );
    }
}
