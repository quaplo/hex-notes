<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Projection;

use Exception;
use DateTimeInterface;
use App\Project\Application\Projection\ProjectReadModelProjectionInterface;
use App\Project\Domain\Event\ProjectCreatedEvent;
use App\Project\Domain\Event\ProjectDeletedEvent;
use App\Project\Domain\Event\ProjectRenamedEvent;
use App\Project\Domain\Event\ProjectWorkerAddedEvent;
use App\Project\Domain\Event\ProjectWorkerRemovedEvent;
use App\Project\Infrastructure\Persistence\ReadModel\ProjectReadModelEntity;
use App\Project\Infrastructure\Persistence\ReadModel\ProjectReadModelRepository;
use App\Shared\Domain\Event\DomainEvent;
use Psr\Log\LoggerInterface;

final readonly class ProjectReadModelProjection implements ProjectReadModelProjectionInterface
{
    public function __construct(
        private ProjectReadModelRepository $projectReadModelRepository,
        private LoggerInterface $logger
    ) {
    }

    public function handle(DomainEvent $domainEvent): void
    {
        try {
            match ($domainEvent::class) {
                ProjectCreatedEvent::class => $this->handleProjectCreated($domainEvent),
                ProjectRenamedEvent::class => $this->handleProjectRenamed($domainEvent),
                ProjectDeletedEvent::class => $this->handleProjectDeleted($domainEvent),
                ProjectWorkerAddedEvent::class => $this->handleProjectWorkerAdded($domainEvent),
                ProjectWorkerRemovedEvent::class => $this->handleProjectWorkerRemoved($domainEvent),
                default => $this->logger->debug('Unhandled project event in read model projection', [
                    'event' => $domainEvent::class
                ])
            };
        } catch (Exception $e) {
            $this->logger->error('Failed to update project read model', [
                'event' => $domainEvent::class,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function handleProjectCreated(ProjectCreatedEvent $projectCreatedEvent): void
    {
        $projectReadModelEntity = new ProjectReadModelEntity(
            $projectCreatedEvent->getProjectId()->toString(),
            $projectCreatedEvent->getName()->__toString(),
            $projectCreatedEvent->getOwnerId()->toString(),
            $projectCreatedEvent->getOccurredAt()
        );

        $this->projectReadModelRepository->save($projectReadModelEntity);

        $this->logger->info('Project read model created', [
            'projectId' => $projectCreatedEvent->getProjectId()->toString(),
            'name' => $projectCreatedEvent->getName()->__toString()
        ]);
    }

    private function handleProjectRenamed(ProjectRenamedEvent $projectRenamedEvent): void
    {
        $readModel = $this->projectReadModelRepository->findById($projectRenamedEvent->getProjectId());
        
        if (!$readModel instanceof ProjectReadModelEntity) {
            $this->logger->warning('Project read model not found for rename', [
                'projectId' => $projectRenamedEvent->getProjectId()->toString()
            ]);
            return;
        }

        $readModel->setName($projectRenamedEvent->getNewName()->__toString());
        $readModel->incrementVersion();
        
        $this->projectReadModelRepository->save($readModel);

        $this->logger->info('Project read model renamed', [
            'projectId' => $projectRenamedEvent->getProjectId()->toString(),
            'oldName' => $projectRenamedEvent->getOldName()->__toString(),
            'newName' => $projectRenamedEvent->getNewName()->__toString()
        ]);
    }

    private function handleProjectDeleted(ProjectDeletedEvent $projectDeletedEvent): void
    {
        $readModel = $this->projectReadModelRepository->findById($projectDeletedEvent->getProjectId());
        
        if (!$readModel instanceof ProjectReadModelEntity) {
            $this->logger->warning('Project read model not found for deletion', [
                'projectId' => $projectDeletedEvent->getProjectId()->toString()
            ]);
            return;
        }

        $readModel->setDeletedAt($projectDeletedEvent->getOccurredAt());
        $readModel->incrementVersion();
        
        $this->projectReadModelRepository->save($readModel);

        $this->logger->info('Project read model deleted', [
            'projectId' => $projectDeletedEvent->getProjectId()->toString()
        ]);
    }

    private function handleProjectWorkerAdded(ProjectWorkerAddedEvent $projectWorkerAddedEvent): void
    {
        $readModel = $this->projectReadModelRepository->findById($projectWorkerAddedEvent->getProjectId());
        
        if (!$readModel instanceof ProjectReadModelEntity) {
            $this->logger->warning('Project read model not found for worker addition', [
                'projectId' => $projectWorkerAddedEvent->getProjectId()->toString()
            ]);
            return;
        }

        $workerData = [
            'userId' => $projectWorkerAddedEvent->getUserId()->toString(),
            'role' => (string)$projectWorkerAddedEvent->getRole(),
            'addedBy' => $projectWorkerAddedEvent->getAddedBy()?->toString(),
            'addedAt' => $projectWorkerAddedEvent->getOccurredAt()->format(DateTimeInterface::ATOM)
        ];

        $readModel->addWorker($workerData);
        $readModel->incrementVersion();
        
        $this->projectReadModelRepository->save($readModel);

        $this->logger->info('Project worker added to read model', [
            'projectId' => $projectWorkerAddedEvent->getProjectId()->toString(),
            'userId' => $projectWorkerAddedEvent->getUserId()->toString(),
            'role' => (string)$projectWorkerAddedEvent->getRole()
        ]);
    }

    private function handleProjectWorkerRemoved(ProjectWorkerRemovedEvent $projectWorkerRemovedEvent): void
    {
        $readModel = $this->projectReadModelRepository->findById($projectWorkerRemovedEvent->getProjectId());
        
        if (!$readModel instanceof ProjectReadModelEntity) {
            $this->logger->warning('Project read model not found for worker removal', [
                'projectId' => $projectWorkerRemovedEvent->getProjectId()->toString()
            ]);
            return;
        }

        $readModel->removeWorker($projectWorkerRemovedEvent->getUserId()->toString());
        $readModel->incrementVersion();
        
        $this->projectReadModelRepository->save($readModel);

        $this->logger->info('Project worker removed from read model', [
            'projectId' => $projectWorkerRemovedEvent->getProjectId()->toString(),
            'userId' => $projectWorkerRemovedEvent->getUserId()->toString()
        ]);
    }
}