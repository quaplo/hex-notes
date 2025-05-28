<?php

namespace App\Domain\User\Model;

use App\Shared\ValueObject\Email;

final class User
{
	public function __construct(
		private string             $id,
		private Email             $email,
		private \DateTimeImmutable $createdAt
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getEmail(): Email
	{
		return $this->email;
	}

	public function getCreatedAt(): \DateTimeImmutable
	{
		return $this->createdAt;
	}
}