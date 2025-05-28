<?php

namespace App\Application\User;

use App\Domain\User\Model\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Shared\ValueObject\Email;
use Symfony\Component\Uid\Uuid;

final class RegisterUserHandler
{
	public function __construct(
		private UserRepositoryInterface $userRepository
	)
	{
	}

	public function handle(string $email): void
	{
		$user = new User(
			id: $this->generateUuid(),
			email: new Email($email),
			createdAt: new \DateTimeImmutable()
		);

		$this->userRepository->save($user);
	}

	private function generateUuid(): string
	{
		return Uuid::v4()->toRfc4122();
	}
}