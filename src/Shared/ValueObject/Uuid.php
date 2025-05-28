<?php declare(strict_types=1);

namespace App\Shared\ValueObject;


use InvalidArgumentException;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

final class Uuid
{
	public function __construct(private readonly string $value)
	{
		if (!SymfonyUuid::isValid($value)) {
			throw new InvalidArgumentException("Invalid UUID: $value");
		}
	}

	public static function generate(): self
	{
		return new self(SymfonyUuid::v4()->toRfc4122());
	}

	public function getValue(): string
	{
		return $this->value;
	}

	public function equals(self $other): bool
	{
		return $this->value === $other->value;
	}

	public function __toString(): string
	{
		return $this->value;
	}
}