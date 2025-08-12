<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Projection;

use App\Project\Application\Projection\ProjectReadModelProjectionInterface;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Infrastructure\Persistence\ReadModel\ProjectReadModelEntity;
use App\Project\Infrastructure\Persistence\ReadModel\ProjectReadModelRepository;
use App\Shared\Domain\Event\DomainEvent;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

final readonly class ProjectReadModelProjection implements ProjectReadModelProjectionInterface
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private ProjectReadModelRepository $projectReadModelRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(DomainEvent $domainEvent): void
    {
        $project = $this->loadProject($domainEvent);
        $projectReadModelEntity = $this->syncReadModel($project);

        $this->projectReadModelRepository->save($projectReadModelEntity);

        $this->logger->info('Read model synced', [
            'id' => $project->getId()->toString(),
            'version' => $project->getVersion(),
        ]);
    }

    private function loadProject(DomainEvent $domainEvent): \App\Project\Domain\Model\Project
    {
        // Extract project ID from event data
        $eventData = $domainEvent->getEventData();

        if (!isset($eventData['projectId'])) {
            throw new RuntimeException('Project ID not found in event data');
        }

        $id = \App\Shared\ValueObject\Uuid::create($eventData['projectId']);

        return $this->projectRepository->load($id)
            ?? throw new RuntimeException("Project not found: {$id->toString()}");
    }

    private function syncReadModel(\App\Project\Domain\Model\Project $project): ProjectReadModelEntity
    {
        $readModel = $this->projectReadModelRepository->findById($project->getId())
            ?? new ProjectReadModelEntity(
                $project->getId()->toString(),
                $project->getName()->__toString(),
                $project->getOwnerId()->toString(),
                $project->getCreatedAt()
            );

        // Sync all properties from current project state
        $readModel->setName($project->getName()->__toString());
        $readModel->setDeletedAt($project->getDeletedAt());
        $readModel->setVersion($project->getVersion());
        $readModel->setWorkers(
            array_map(
                fn ($w): array => [
                    'userId' => $w->getUserId()->toString(),
                    'role' => $w->getRole()->toString(),
                    'addedBy' => $w->getAddedBy()->toString(),
                    'addedAt' => $w->getCreatedAt()->format(DateTimeInterface::ATOM),
                ],
                $project->getWorkers()
            )
        );

        return $readModel;
    }
}
