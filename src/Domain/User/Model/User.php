<?php declare(strict_types=1);

namespace App\Domain\User\Model;

use App\Shared\ValueObject\Email;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final class User
{
	public function __construct(
		private Uuid               $id,
		private Email              $email,
		private DateTimeImmutable $createdAt
	)
	{
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