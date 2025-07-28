<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AddItemRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Product ID cannot be empty')]
        #[Assert\Uuid(message: 'Product ID must be a valid UUID')]
        public string $productId,
        #[Assert\NotBlank(message: 'Product name cannot be empty')]
        #[Assert\Length(min: 1, max: 255, minMessage: 'Product name must be at least 1 character', maxMessage: 'Product name cannot exceed 255 characters')]
        public string $productName,
        #[Assert\NotBlank(message: 'Quantity cannot be empty')]
        #[Assert\Type(type: 'integer', message: 'Quantity must be an integer')]
        #[Assert\Positive(message: 'Quantity must be positive')]
        public int $quantity,
        #[Assert\NotBlank(message: 'Unit price cannot be empty')]
        #[Assert\Type(type: 'numeric', message: 'Unit price must be a number')]
        #[Assert\Positive(message: 'Unit price must be positive')]
        public float $unitPrice,
        #[Assert\NotBlank(message: 'Currency cannot be empty')]
        #[Assert\Choice(choices: ['CZK', 'EUR', 'USD', 'GBP'], message: 'Currency must be one of: CZK, EUR, USD, GBP')]
        public string $currency
    ) {
    }
}