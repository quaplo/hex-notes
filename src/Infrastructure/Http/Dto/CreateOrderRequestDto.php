<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateOrderRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Currency cannot be empty')]
        #[Assert\Choice(choices: ['CZK', 'EUR', 'USD', 'GBP'], message: 'Currency must be one of: CZK, EUR, USD, GBP')]
        public string $currency
    ) {
    }
}
