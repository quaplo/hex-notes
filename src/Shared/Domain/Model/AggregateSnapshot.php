<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model;

use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

interface AggregateSnapshot
{
    public function getAggregateId(): Uuid;

    public function getAggregateType(): string;

    public function getVersion(): int;

    public function getData(): array;

    public function getCreatedAt(): DateTimeImmutable;
}
