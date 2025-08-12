<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class DeleteProjectRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $userId,
    ) {
    }
}
