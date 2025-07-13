<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Exception;

use Exception;
use Throwable;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class ValidationException extends Exception
{
    public function __construct(
        private readonly ConstraintViolationListInterface $constraintViolationList,
        string $message = 'Validation failed',
        int $code = 400,
        ?Throwable $throwable = null
    ) {
        parent::__construct($message, $code, $throwable);
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->constraintViolationList;
    }
}
