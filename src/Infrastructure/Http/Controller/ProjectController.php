<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Project\Application\Command\RegisterProjectCommand;
use App\Project\Application\Command\RegisterProjectHandler;
use App\Project\Application\Query\GetProjectQuery;
use App\Project\Application\Query\GetProjectHandler;
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
        private readonly GetProjectHandler $getProjectHandler,
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
}
