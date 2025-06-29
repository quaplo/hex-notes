<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Project\Command\RegisterProjectCommand;
use App\Application\Project\Command\RegisterProjectHandler;
use App\Infrastructure\Http\Request\CreateProjectRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class ProjectController
{
    public function __construct(
        private readonly RegisterProjectHandler $registerProjectHandler,
        private readonly SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/projects', name: 'create_project', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var CreateProjectRequest $dto */
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            CreateProjectRequest::class,
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
