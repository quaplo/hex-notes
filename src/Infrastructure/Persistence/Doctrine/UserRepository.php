<?php
// src/Infrastructure/Persistence/Doctrine/UserRepository.php
namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\User\Model\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use App\Shared\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;

final class UserRepository implements UserRepositoryInterface
{
	public function __construct(private EntityManagerInterface $em) {}

	public function save(User $user): void
	{
		$entity = new UserEntity(
			$user->getId(),
			$user->getEmail()->getValue(),
			$user->getCreatedAt()
		);

		$this->em->persist($entity);
		$this->em->flush();
	}

	public function findByEmail(string $email): ?User
	{
		$repo = $this->em->getRepository(UserEntity::class);
		$entity = $repo->findOneBy(['email' => $email]);

		if (!$entity) {
			return null;
		}

		return new User(
			$entity->getId(),
			new Email($entity->getEmail()),
			$entity->getCreatedAt()
		);
	}
}
