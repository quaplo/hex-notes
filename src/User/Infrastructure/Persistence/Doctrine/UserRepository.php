<?php

namespace App\User\Infrastructure\Persistence\Doctrine;

use DateTimeImmutable;
use App\Shared\ValueObject\Uuid;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Model\User;
use App\User\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use App\Shared\ValueObject\Email;
use App\Shared\Event\EventDispatcher;
use Doctrine\ORM\EntityManagerInterface;

final readonly class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventDispatcher $eventDispatcher
    ) {
    }

    public function save(User $user): void
    {
        $existingEntity = $this->entityManager->getRepository(UserEntity::class)
            ->findOneBy(['id' => $user->getId()->toString()]);

        if ($existingEntity instanceof UserEntity) {
            // Update existing entity
            $existingEntity->setEmail($user->getEmail()->__toString());
            $existingEntity->setStatus($user->getStatus()->value);
            $existingEntity->setDeletedAt($user->getDeletedAt());
        } else {
            // Create new entity
            $userEntity = new UserEntity(
                $user->getId()->toString(),
                $user->getEmail()->__toString(),
                $user->getStatus()->value,
                $user->getCreatedAt(),
                $user->getDeletedAt()
            );
            $this->entityManager->persist($userEntity);
        }

        $this->entityManager->flush();

        // Dispatch domain events after successful persistence
        if ($user->hasUncommittedEvents()) {
            $this->eventDispatcher->dispatch($user->getUncommittedEvents());
            $user->markEventsAsCommitted();
        }
    }

    public function delete(Uuid $uuid): void
    {
        $entity = $this->entityManager->getRepository(UserEntity::class)->findOneBy(['id' => $uuid->toString()]);

        if (!$entity instanceof UserEntity) {
            return; // User not found, nothing to delete
        }

        if ($entity->getDeletedAt() instanceof DateTimeImmutable) {
            return; // Already soft deleted
        }

        $user = $this->mapToDomain($entity);
        $user->delete();
        $this->save($user);
    }

    public function findById(Uuid $uuid): ?User
    {
        $entity = $this->entityManager->getRepository(UserEntity::class)->findOneBy([
            'id' => $uuid->__toString(),
            'deletedAt' => null
        ]);
        if (!$entity instanceof UserEntity) {
            return null;
        }
        return $this->mapToDomain($entity);
    }

    public function findByEmail(Email $email): ?User
    {
        $entity = $this->entityManager->getRepository(UserEntity::class)->findOneBy([
            'email' => $email->__toString(),
            'deletedAt' => null
        ]);
        if (!$entity instanceof UserEntity) {
            return null;
        }
        return $this->mapToDomain($entity);
    }

    public function findByIdIncludingDeleted(Uuid $uuid): ?User
    {
        $entity = $this->entityManager->getRepository(UserEntity::class)->findOneBy(['id' => $uuid->__toString()]);
        if (!$entity instanceof UserEntity) {
            return null;
        }
        return $this->mapToDomain($entity);
    }

    public function findByEmailIncludingDeleted(Email $email): ?User
    {
        $entity = $this->entityManager->getRepository(UserEntity::class)->findOneBy(['email' => $email->__toString()]);
        if (!$entity instanceof UserEntity) {
            return null;
        }
        return $this->mapToDomain($entity);
    }

    private function mapToDomain(UserEntity $userEntity): User
    {
        return User::fromPrimitives(
            $userEntity->getId(),
            $userEntity->getEmail(),
            $userEntity->getStatus(),
            $userEntity->getCreatedAt(),
            $userEntity->getDeletedAt()
        );
    }
}
