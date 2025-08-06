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

final readonly class ProjectReadModelProjection implements ProjectReadModelProjectionInterface
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private ProjectReadModelRepository $readModelRepository,
        private LoggerInterface $logger
    ) {
    }

    public function handle(DomainEvent $event): void
    {
        $project = $this->loadProject($event);
        $readModel = $this->syncReadModel($project);

        $this->readModelRepository->save($readModel);

        $this->logger->info('Read model synced', [
            'id' => $project->getId()->toString(),
            'version' => $project->getVersion()
        ]);
    }

    private function loadProject(DomainEvent $event)
    {
        // All project events have getProjectId() method - no need for method_exists()
        $id = $event->getProjectId();

        return $this->projectRepository->load($id)
            ?? throw new \RuntimeException("Project not found: {$id->toString()}");
    }

    private function syncReadModel($project): ProjectReadModelEntity
    {
        $readModel = $this->readModelRepository->findById($project->getId())
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
                fn($w) => [
                    'userId' => $w->getUserId()->toString(),
                    'role' => $w->getRole()->__toString(),
                    'addedBy' => $w->getAddedBy()->toString(),
                    'addedAt' => $w->getCreatedAt()->format(DateTimeInterface::ATOM)
                ],
                $project->getWorkers()
            )
        );

        return $readModel;
    }
}
