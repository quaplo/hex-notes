<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Infrastructure\Http\Dto\AddProjectWorkerRequestDto;
use App\Infrastructure\Http\Dto\CreateProjectRequestDto;
use App\Infrastructure\Http\Dto\DeleteProjectRequestDto;
use App\Infrastructure\Http\Dto\RemoveProjectWorkerRequestDto;
use App\Infrastructure\Http\Dto\RenameProjectRequestDto;
use App\Infrastructure\Http\Exception\ValidationException;
use App\Infrastructure\Http\Mapper\ProjectDtoMapper;
use App\Project\Application\Command\Delete\DeleteProjectCommand;
use App\Project\Application\Command\Register\RegisterProjectCommand;
use App\Project\Application\Command\Rename\RenameProjectCommand;
use App\Project\Application\Command\Worker\AddProjectWorkerCommand;
use App\Project\Application\Command\Worker\RemoveProjectWorkerCommand;
use App\Project\Application\Query\Get\GetProjectHistoryQuery;
use App\Shared\Application\CommandBus;
use App\Shared\Application\CrossDomain\Query\GetProjectWithUserDetailsQuery;
use App\Shared\Application\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ProjectController extends BaseController
{
    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
        private readonly ProjectDtoMapper $projectDtoMapper,
    ) {
        parent::__construct($serializer, $validator);
    }

    #[Route('/api/projects', name: 'create_project', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            /** @var CreateProjectRequestDto $dto */
            $dto = $this->deserializeAndValidate($request, CreateProjectRequestDto::class);

            // For now, use a default owner ID if not provided (in real app, get from authentication)
            $ownerId = $dto->ownerId ?? '550e8400-e29b-41d4-a716-446655440001';
            $command = RegisterProjectCommand::fromPrimitives($dto->name, $ownerId);
            $project = $this->commandBus->dispatch($command);
            $projectDto = $this->projectDtoMapper->toDto($project);

            return new JsonResponse($projectDto, JsonResponse::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->createValidationErrorResponse($e->getViolations());
        }
    }

    #[Route('/api/projects/{id}', name: 'get_project', methods: ['GET'])]
    public function detail(string $id): JsonResponse
    {
        $getProjectWithUserDetailsQuery = GetProjectWithUserDetailsQuery::fromPrimitives($id);
        $dto = $this->queryBus->dispatch($getProjectWithUserDetailsQuery);

        if (!$dto) {
            return new JsonResponse(['error' => 'Project not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse($dto, JsonResponse::HTTP_OK);
    }

    #[Route('/api/projects/{id}/workers', name: 'add_project_worker', methods: ['POST'])]
    public function addWorker(string $id, Request $request): JsonResponse
    {
        try {
            /** @var AddProjectWorkerRequestDto $dto */
            $dto = $this->deserializeAndValidate($request, AddProjectWorkerRequestDto::class);

            // For now, use a default addedBy ID if not provided (in real app, get from authentication)
            $addedBy = $dto->addedBy ?? '550e8400-e29b-41d4-a716-446655440001';
            $command = AddProjectWorkerCommand::fromPrimitives(
                $id,
                $dto->userId,
                $dto->role,
                $addedBy
            );
            $this->commandBus->dispatch($command);

            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (ValidationException $e) {
            return $this->createValidationErrorResponse($e->getViolations());
        }
    }

    #[Route('/api/projects/{id}/workers', name: 'remove_project_worker', methods: ['DELETE'])]
    public function removeWorker(string $id, Request $request): JsonResponse
    {
        try {
            /** @var RemoveProjectWorkerRequestDto $dto */
            $dto = $this->deserializeAndValidate($request, RemoveProjectWorkerRequestDto::class);

            $command = RemoveProjectWorkerCommand::fromPrimitives(
                $id,
                $dto->userId,
                $dto->removedBy
            );
            $this->commandBus->dispatch($command);

            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (ValidationException $e) {
            return $this->createValidationErrorResponse($e->getViolations());
        }
    }

    #[Route('/api/projects/{id}', name: 'rename_project', methods: ['PUT'])]
    public function rename(string $id, Request $request): JsonResponse
    {
        try {
            /** @var RenameProjectRequestDto $dto */
            $dto = $this->deserializeAndValidate($request, RenameProjectRequestDto::class);

            $command = RenameProjectCommand::fromPrimitives($id, $dto->name);
            $project = $this->commandBus->dispatch($command);
            $projectDto = $this->projectDtoMapper->toDto($project);

            return new JsonResponse($projectDto, JsonResponse::HTTP_OK);
        } catch (ValidationException $e) {
            return $this->createValidationErrorResponse($e->getViolations());
        }
    }

    #[Route('/api/projects/{id}', name: 'delete_project', methods: ['DELETE'])]
    public function delete(string $id, Request $request): JsonResponse
    {
        try {
            /** @var DeleteProjectRequestDto $dto */
            $dto = $this->deserializeAndValidate($request, DeleteProjectRequestDto::class);

            $deleteProjectCommand = DeleteProjectCommand::fromPrimitives($id, $dto->userId);
            $this->commandBus->dispatch($deleteProjectCommand);

            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (ValidationException $e) {
            return $this->createValidationErrorResponse($e->getViolations());
        }
    }

    #[Route('/api/projects/{id}/history', name: 'get_project_history', methods: ['GET'])]
    public function history(string $id): JsonResponse
    {
        $getProjectHistoryQuery = GetProjectHistoryQuery::fromPrimitives($id);
        $history = $this->queryBus->dispatch($getProjectHistoryQuery);

        return new JsonResponse($history, JsonResponse::HTTP_OK);
    }
}
