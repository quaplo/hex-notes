<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Project\Application\Command\RegisterProjectCommand;
use App\Project\Application\Command\RegisterProjectHandler;
use App\Project\Application\Command\RenameProjectCommand;
use App\Project\Application\Command\RenameProjectHandler;
use App\Project\Application\Command\DeleteProjectCommand;
use App\Project\Application\Command\DeleteProjectHandler;
use App\Project\Application\Query\GetProjectQuery;
use App\Project\Application\Query\GetProjectHandler;
use App\Project\Application\Query\GetProjectHistoryQuery;
use App\Project\Application\Query\GetProjectHistoryHandler;
use App\Infrastructure\Http\Dto\CreateProjectRequestDto;
use App\Infrastructure\Http\Mapper\ProjectDtoMapper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use App\Project\Application\Composite\Query\GetProjectFullDetailQuery;
use App\Project\Application\Composite\Query\GetProjectFullDetailHandler;
use App\Project\Application\Composite\Dto\ProjectFullDetailDto;
use App\Infrastructure\Http\Dto\AddProjectWorkerRequestDto;
use App\Project\Application\Command\AddProjectWorkerHandler;
use App\Project\Application\Command\AddProjectWorkerCommand;
use App\Infrastructure\Http\Dto\RemoveProjectWorkerRequestDto;
use App\Project\Application\Command\RemoveProjectWorkerHandler;
use App\Project\Application\Command\RemoveProjectWorkerCommand;

final class ProjectController
{
    public function __construct(
        private readonly RegisterProjectHandler $registerProjectHandler,
        private readonly RenameProjectHandler $renameProjectHandler,
        private readonly DeleteProjectHandler $deleteProjectHandler,
        private readonly GetProjectHandler $getProjectHandler,
        private readonly GetProjectHistoryHandler $getProjectHistoryHandler,
        private readonly ProjectDtoMapper $projectDtoMapper,
        private readonly SerializerInterface $serializer,
        private readonly GetProjectFullDetailHandler $getProjectFullDetailHandler,
        private readonly AddProjectWorkerHandler $addProjectWorkerHandler,
        private readonly RemoveProjectWorkerHandler $removeProjectWorkerHandler,
    ) {
    }

    #[Route('/api/projects', name: 'create_project', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var CreateProjectRequestDto $dto */
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            CreateProjectRequestDto::class,
            'json'
        );

        $command = RegisterProjectCommand::fromPrimitives($dto->name, $dto->ownerId);
        $project = ($this->registerProjectHandler)($command);
        $projectDto = $this->projectDtoMapper->toDto($project);

        return new JsonResponse($projectDto, JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/projects/{id}', name: 'get_project', methods: ['GET'])]
    public function detail(string $id): JsonResponse
    {
        $query = GetProjectFullDetailQuery::fromPrimitives($id);
        $dto = ($this->getProjectFullDetailHandler)($query);

        if (!$dto) {
            return new JsonResponse(['error' => 'Project not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse($dto, JsonResponse::HTTP_OK);
    }

    #[Route('/api/projects/{id}/workers', name: 'add_project_worker', methods: ['POST'])]
    public function addWorker(string $id, Request $request): JsonResponse
    {
        /** @var AddProjectWorkerRequestDto $dto */
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            AddProjectWorkerRequestDto::class,
            'json'
        );

        $command = AddProjectWorkerCommand::fromPrimitives(
            $id,
            $dto->userId,
            $dto->role,
            $dto->addedBy
        );
        ($this->addProjectWorkerHandler)($command);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/api/projects/{id}/workers', name: 'remove_project_worker', methods: ['DELETE'])]
    public function removeWorker(string $id, Request $request): JsonResponse
    {
        /** @var RemoveProjectWorkerRequestDto $dto */
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            RemoveProjectWorkerRequestDto::class,
            'json'
        );

        $command = RemoveProjectWorkerCommand::fromPrimitives(
            $id,
            $dto->userId,
            $dto->removedBy
        );
        ($this->removeProjectWorkerHandler)($command);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/api/projects/{id}', name: 'rename_project', methods: ['PUT'])]
    public function rename(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['name'])) {
            return new JsonResponse(['error' => 'Name is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $command = RenameProjectCommand::fromPrimitives($id, $data['name']);
        $project = ($this->renameProjectHandler)($command);
        $projectDto = $this->projectDtoMapper->toDto($project);

        return new JsonResponse($projectDto, JsonResponse::HTTP_OK);
    }

    #[Route('/api/projects/{id}', name: 'delete_project', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $command = DeleteProjectCommand::fromPrimitives($id);
        ($this->deleteProjectHandler)($command);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/api/projects/{id}/history', name: 'get_project_history', methods: ['GET'])]
    public function history(string $id): JsonResponse
    {
        $query = GetProjectHistoryQuery::fromPrimitives($id);
        $history = ($this->getProjectHistoryHandler)($query);

        return new JsonResponse($history, JsonResponse::HTTP_OK);
    }
}
