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

final class ProjectController
{
    public function __construct(
        private readonly RegisterProjectHandler $registerProjectHandler,
        private readonly GetProjectHandler $getProjectHandler,
        private readonly ProjectDtoMapper $projectDtoMapper,
        private readonly SerializerInterface $serializer,
    ) {}

    #[Route('/api/projects', name: 'create_project', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var CreateProjectRequestDto $dto */
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            CreateProjectRequestDto::class,
            'json'
        );

        $command = new RegisterProjectCommand($dto->name, $dto->ownerId);
        $project = ($this->registerProjectHandler)($command);
        $projectDto = $this->projectDtoMapper->toDto($project);

        return new JsonResponse($projectDto, JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/projects/{id}', name: 'get_project', methods: ['GET'])]
    public function detail(string $id): JsonResponse
    {
        $query = new GetProjectQuery($id);
        $projectDto = ($this->getProjectHandler)($query);

        if (!$projectDto) {
            return new JsonResponse(['error' => 'Project not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse($projectDto, JsonResponse::HTTP_OK);
    }
}
