<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Infrastructure\Http\Dto\CreateUserRequestDto;
use App\Infrastructure\Http\Exception\ValidationException;
use App\User\Application\Command\CreateUserCommand;
use App\User\Application\Query\GetUserByIdQuery;
use App\Shared\Application\CommandBus;
use App\Shared\Application\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UserController extends BaseController
{
    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {
        parent::__construct($serializer, $validator);
    }

    #[Route('/api/users', name: 'create_user', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            /** @var CreateUserRequestDto $dto */
            $dto = $this->deserializeAndValidate($request, CreateUserRequestDto::class);

            $command = new CreateUserCommand($dto->email);
            $user = $this->commandBus->dispatch($command);

            return new JsonResponse([
                'message' => 'User created successfully',
                'id' => $user->getId()->toString(),
                'email' => $user->getEmail()->__toString()
            ], JsonResponse::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->createValidationErrorResponse($e->getViolations());
        }
    }

    #[Route('/api/users/{id}', name: 'get_user_by_id', methods: ['GET'])]
    public function getById(string $id): JsonResponse
    {
        $query = new GetUserByIdQuery($id);
        $userDto = $this->queryBus->dispatch($query);

        if (!$userDto) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse($userDto, JsonResponse::HTTP_OK);
    }
}
