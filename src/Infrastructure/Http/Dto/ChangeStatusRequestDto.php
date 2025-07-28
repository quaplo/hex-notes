<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChangeStatusRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Status cannot be empty')]
        #[Assert\Choice(choices: ['CREATED', 'CONFIRMED', 'PAID', 'SHIPPED', 'DELIVERED', 'CANCELLED', 'REFUNDED'], message: 'Status must be one of: CREATED, CONFIRMED, PAID, SHIPPED, DELIVERED, CANCELLED, REFUNDED')]
        public string $status
    ) {
    }
}