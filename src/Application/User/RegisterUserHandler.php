<?php declare(strict_types=1);

namespace App\Application\User;

use App\Application\Exception\EmailAlreadyExistsException;
use App\Domain\User\Model\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Shared\ValueObject\Email;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;


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
			id: Uuid::generate(),
			email: new Email($email),
			createdAt: new DateTimeImmutable()
		);

		try {
			$this->userRepository->save($user);
		} catch (EmailAlreadyExistsException $e) {
			// Môžeš zalogovať, auditovať alebo preniesť vyššie
			throw $e;
		}
	}
}