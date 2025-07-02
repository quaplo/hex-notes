<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use App\Infrastructure\Http\Exception\ValidationException;

abstract class BaseController
{
    public function __construct(
        protected readonly SerializerInterface $serializer,
        protected readonly ValidatorInterface $validator
    ) {
    }

    /**
     * Deserialize and validate DTO from request
     */
    protected function deserializeAndValidate(Request $request, string $dtoClass): object
    {
        /** @var object $dto */
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            $dtoClass,
            'json'
        );

        $violations = $this->validator->validate($dto);

        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }

        return $dto;
    }

    /**
     * Create validation error response
     */
    protected function createValidationErrorResponse(ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return new JsonResponse([
            'error' => 'Validation failed',
            'violations' => $errors
        ], JsonResponse::HTTP_BAD_REQUEST);
    }
}