<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Infrastructure\Http\Dto\CreateUserRequestDto;
use App\Infrastructure\Http\Dto\UserDto;
use App\Infrastructure\Http\Exception\ValidationException;
use App\Shared\Application\CommandBus;
use App\Shared\Application\QueryBus;
use App\User\Application\Command\Create\CreateUserCommand;
use App\User\Application\Command\Delete\DeleteUserCommand;
use App\User\Application\Exception\UserNotFoundException;
use App\User\Application\Query\Get\GetUserByIdQuery;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
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
    #[OA\Post(
        path: '/api/users',
        summary: 'Vytvorenie nového používateľa',
        description: 'Vytvorí nového používateľa v systéme',
        requestBody: new OA\RequestBody(
            description: 'Údaje pre vytvorenie používateľa',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateUserRequestDto::class))
        ),
        tags: ['Users'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Používateľ úspešne vytvorený',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'User created successfully'),
                        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com')
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Chyba validácie',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Validation failed'),
                        new OA\Property(property: 'violations', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        try {
            /** @var CreateUserRequestDto $dto */
            $dto = $this->deserializeAndValidate($request, CreateUserRequestDto::class);

            $createUserCommand = CreateUserCommand::fromPrimitives($dto->email);
            $user = $this->commandBus->dispatch($createUserCommand);

            return new JsonResponse([
                'message' => 'User created successfully',
                'id' => $user->getId()->toString(),
                'email' => $user->getEmail()->__toString(),
            ], JsonResponse::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->createValidationErrorResponse($e->getViolations());
        }
    }

    #[Route('/api/users/{id}', name: 'get_user_by_id', methods: ['GET'])]
    #[OA\Get(
        path: '/api/users/{id}',
        summary: 'Získanie používateľa podľa ID',
        description: 'Vráti údaje používateľa na základe jeho ID',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'UUID používateľa',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Údaje používateľa',
                content: new OA\JsonContent(ref: new Model(type: UserDto::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Používateľ nenájdený',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'User not found')
                    ]
                )
            )
        ]
    )]
    public function getById(string $id): JsonResponse
    {
        $getUserByIdQuery = GetUserByIdQuery::fromPrimitives($id);
        $userDto = $this->queryBus->dispatch($getUserByIdQuery);

        if (!$userDto) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse($userDto, JsonResponse::HTTP_OK);
    }

    #[Route('/api/users/{id}', name: 'delete_user', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/users/{id}',
        summary: 'Zmazanie používateľa',
        description: 'Zmaže používateľa zo systému',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'UUID používateľa',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Používateľ úspešne zmazaný',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'User deleted successfully')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Používateľ nenájdený',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'User not found')
                    ]
                )
            )
        ]
    )]
    public function delete(string $id): JsonResponse
    {
        try {
            $deleteUserCommand = DeleteUserCommand::fromPrimitives($id);
            $this->commandBus->dispatch($deleteUserCommand);

            return new JsonResponse([
                'message' => 'User deleted successfully',
            ], JsonResponse::HTTP_OK);
        } catch (HandlerFailedException $e) {
            // Check if the original exception is UserNotFoundException
            $previous = $e->getPrevious();

            if ($previous instanceof UserNotFoundException) {
                return new JsonResponse([
                    'error' => 'User not found',
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            // Re-throw if it's a different exception
            throw $e;
        } catch (UserNotFoundException) {
            return new JsonResponse([
                'error' => 'User not found',
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }
}
