<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\Exception\EmailAlreadyExistsException;
use App\Domain\User\Model\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use App\Shared\ValueObject\Email;
use App\Shared\ValueObject\Uuid;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final class UserRepository implements UserRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function save(User $user): void
    {
        try {
            $entity = new UserEntity(
                $user->getId()->toString(),
                $user->getEmail()->getValue(),
                $user->getCreatedAt()
            );

            $this->em->persist($entity);
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new EmailAlreadyExistsException($user->getEmail()->getValue(), $e);
        }
    }

    public function findByEmail(string $email): ?User
    {
        $repo = $this->em->getRepository(UserEntity::class);
        $entity = $repo->findOneBy(['email' => $email]);

        if (!$entity) {
            return null;
        }

        return new User(
            new Uuid($entity->getId()),
            new Email($entity->getEmail()),
            $entity->getCreatedAt()
        );
    }
}
