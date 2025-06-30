<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Infrastructure\Http\Dto\CreateProjectRequestDto;
use App\Project\Application\Command\RegisterProjectCommand;
use App\Project\Application\Command\RegisterProjectHandler;
use App\Project\Application\Query\GetProjectHandler;
use App\Project\Application\Query\GetProjectQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class ProjectController
{
    public function __construct(
        private readonly RegisterProjectHandler $registerProjectHandler,
        private readonly SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/projects/{id}', name: 'detail', methods: ['GET'])]
    public function detail(
        string $id,
        GetProjectHandler $handler
    ): JsonResponse {
        try {
            $dto = $handler(new GetProjectQuery($id));

            $json = $this->serializer->serialize($dto, 'json');

            return new JsonResponse($json, JsonResponse::HTTP_OK, [], true);
        } catch (\Throwable $e) {
            throw new NotFoundHttpException('Project not found.');
        }
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

        $command = new RegisterProjectCommand($dto->name, $dto->ownerEmail);
        $project = ($this->registerProjectHandler)($command);

        return new JsonResponse([
            'id' => $project->getId()->toString(),
            'name' => (string) $project->getName(),
            'createdAt' => $project->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ], JsonResponse::HTTP_CREATED);
    }
}
