<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RemoveItemRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Order item ID cannot be empty')]
        #[Assert\Uuid(message: 'Order item ID must be a valid UUID')]
        public string $orderItemId
    ) {
    }
}
