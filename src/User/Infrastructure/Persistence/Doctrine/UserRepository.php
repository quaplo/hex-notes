<?php

namespace App\User\Infrastructure\Persistence\Doctrine;

use App\Shared\ValueObject\Uuid;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Model\User;
use App\User\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use App\Shared\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;

final class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    public function save(User $user): void
    {
        $existingEntity = $this->em->getRepository(UserEntity::class)->findOneBy(['id' => $user->getId()->toString()]);
        
        if ($existingEntity) {
            // Update existing entity
            $existingEntity->setEmail($user->getEmail()->__toString());
            $existingEntity->setStatus($user->getStatus()->value);
        } else {
            // Create new entity
            $entity = new UserEntity(
                $user->getId()->toString(),
                $user->getEmail()->__toString(),
                $user->getStatus()->value,
                $user->getCreatedAt()
            );
            $this->em->persist($entity);
        }
        
        $this->em->flush();
    }

    public function findById(Uuid $id): ?User
    {
        $entity = $this->em->getRepository(UserEntity::class)->findOneBy(['id' => $id->__toString()]);
        if (!$entity) {
            return null;
        }
        return $this->mapToDomain($entity);
    }

    public function findByEmail(Email $email): ?User
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
            $entity->getCreatedAt()
        );
    }
}
