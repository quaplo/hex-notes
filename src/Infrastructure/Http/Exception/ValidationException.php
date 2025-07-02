<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

final class ValidationException extends \Exception
{
    public function __construct(
        private readonly ConstraintViolationListInterface $violations,
        string $message = 'Validation failed',
        int $code = 400,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }
}