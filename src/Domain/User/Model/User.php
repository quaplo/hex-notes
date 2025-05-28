<?php

namespace App\Domain\User\Model;

final class User
{
	public function __construct(
		private string             $id,
		private string             $email,
		private \DateTimeImmutable $createdAt
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function getCreatedAt(): \DateTimeImmutable
	{
		return $this->createdAt;
	}
}