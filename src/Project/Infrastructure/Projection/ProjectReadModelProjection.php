<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Projection;

use App\Project\Domain\Event\ProjectCreatedEvent;
use App\Project\Domain\Event\ProjectDeletedEvent;
use App\Project\Domain\Event\ProjectRenamedEvent;
use App\Project\Domain\Event\ProjectWorkerAddedEvent;
use App\Project\Domain\Event\ProjectWorkerRemovedEvent;
use App\Project\Infrastructure\Persistence\ReadModel\ProjectReadModelEntity;
use App\Project\Infrastructure\Persistence\ReadModel\ProjectReadModelRepository;
use App\Shared\Domain\Event\DomainEvent;
use Psr\Log\LoggerInterface;

final class ProjectReadModelProjection
{
    public function __construct(
        private readonly ProjectReadModelRepository $readModelRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function handle(DomainEvent $event): void
    {
        try {
            match (get_class($event)) {
                ProjectCreatedEvent::class => $this->handleProjectCreated($event),
                ProjectRenamedEvent::class => $this->handleProjectRenamed($event),
                ProjectDeletedEvent::class => $this->handleProjectDeleted($event),
                ProjectWorkerAddedEvent::class => $this->handleProjectWorkerAdded($event),
                ProjectWorkerRemovedEvent::class => $this->handleProjectWorkerRemoved($event),
                default => $this->logger->debug('Unhandled project event in read model projection', [
                    'event' => get_class($event)
                ])
            };
        } catch (\Exception $e) {
            $this->logger->error('Failed to update project read model', [
                'event' => get_class($event),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function handleProjectCreated(ProjectCreatedEvent $event): void
    {
        $readModel = new ProjectReadModelEntity(
            $event->getProjectId()->toString(),
            $event->getName()->__toString(),
            $event->getOwnerId()->toString(),
            $event->getOccurredAt()
        );

        $this->readModelRepository->save($readModel);

        $this->logger->info('Project read model created', [
            'projectId' => $event->getProjectId()->toString(),
            'name' => $event->getName()->__toString()
        ]);
    }

    private function handleProjectRenamed(ProjectRenamedEvent $event): void
    {
        $readModel = $this->readModelRepository->findById($event->getProjectId());
        
        if (!$readModel) {
            $this->logger->warning('Project read model not found for rename', [
                'projectId' => $event->getProjectId()->toString()
            ]);
            return;
        }

        $readModel->setName($event->getNewName()->__toString());
        $readModel->incrementVersion();
        
        $this->readModelRepository->save($readModel);

        $this->logger->info('Project read model renamed', [
            'projectId' => $event->getProjectId()->toString(),
            'oldName' => $event->getOldName()->__toString(),
            'newName' => $event->getNewName()->__toString()
        ]);
    }

    private function handleProjectDeleted(ProjectDeletedEvent $event): void
    {
        $readModel = $this->readModelRepository->findById($event->getProjectId());
        
        if (!$readModel) {
            $this->logger->warning('Project read model not found for deletion', [
                'projectId' => $event->getProjectId()->toString()
            ]);
            return;
        }

        $readModel->setDeletedAt($event->getOccurredAt());
        $readModel->incrementVersion();
        
        $this->readModelRepository->save($readModel);

        $this->logger->info('Project read model deleted', [
            'projectId' => $event->getProjectId()->toString()
        ]);
    }

    private function handleProjectWorkerAdded(ProjectWorkerAddedEvent $event): void
    {
        $readModel = $this->readModelRepository->findById($event->getProjectId());
        
        if (!$readModel) {
            $this->logger->warning('Project read model not found for worker addition', [
                'projectId' => $event->getProjectId()->toString()
            ]);
            return;
        }

        $workerData = [
            'userId' => $event->getUserId()->toString(),
            'role' => (string)$event->getRole(),
            'addedBy' => $event->getAddedBy()?->toString(),
            'addedAt' => $event->getOccurredAt()->format(\DateTimeInterface::ATOM)
        ];

        $readModel->addWorker($workerData);
        $readModel->incrementVersion();
        
        $this->readModelRepository->save($readModel);

        $this->logger->info('Project worker added to read model', [
            'projectId' => $event->getProjectId()->toString(),
            'userId' => $event->getUserId()->toString(),
            'role' => (string)$event->getRole()
        ]);
    }

    private function handleProjectWorkerRemoved(ProjectWorkerRemovedEvent $event): void
    {
        $readModel = $this->readModelRepository->findById($event->getProjectId());
        
        if (!$readModel) {
            $this->logger->warning('Project read model not found for worker removal', [
                'projectId' => $event->getProjectId()->toString()
            ]);
            return;
        }

        $readModel->removeWorker($event->getUserId()->toString());
        $readModel->incrementVersion();
        
        $this->readModelRepository->save($readModel);

        $this->logger->info('Project worker removed from read model', [
            'projectId' => $event->getProjectId()->toString(),
            'userId' => $event->getUserId()->toString()
        ]);
    }
}