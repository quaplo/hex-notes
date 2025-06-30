<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\User\Command\CreateUserCommand;
use App\Application\User\Command\CreateUserHandler;
use App\Application\User\Query\GetUserByIdQuery;
use App\Application\User\Query\GetUserByIdHandler;
use App\Infrastructure\Http\Dto\CreateUserRequestDto;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class UserController
{
    public function __construct(
        private readonly CreateUserHandler $createUserHandler,
        private readonly GetUserByIdHandler $getUserByIdHandler,
        private readonly SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/users', name: 'create_user', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var CreateUserRequestDto $dto */
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            CreateUserRequestDto::class,
            'json'
        );

        $command = new CreateUserCommand($dto->email);
        $user = ($this->createUserHandler)($command);

        return new JsonResponse([
            'message' => 'User created successfully',
            'id' => $user->getId()->toString(),
            'email' => $user->getEmail()->__toString()
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/users/{id}', name: 'get_user_by_id', methods: ['GET'])]
    public function getById(string $id): JsonResponse
    {
        $query = new GetUserByIdQuery($id);
        $userDto = ($this->getUserByIdHandler)($query);

        if (!$userDto) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse($userDto, JsonResponse::HTTP_OK);
    }
} 