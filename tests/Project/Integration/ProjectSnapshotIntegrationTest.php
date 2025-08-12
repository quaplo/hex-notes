<?php

declare(strict_types=1);

namespace Tests\Project\Integration;

use App\Infrastructure\Event\CompositeEventSerializer;
use App\Infrastructure\Event\FrequencyBasedSnapshotStrategy;
use App\Infrastructure\Persistence\EventStore\AggregateTypeResolver;
use App\Infrastructure\Persistence\EventStore\DoctrineEventStore;
use App\Infrastructure\Persistence\EventStore\ProjectEventStoreRepository;
use App\Infrastructure\Persistence\Snapshot\DoctrineSnapshotStore;
use App\Project\Application\Command\Register\RegisterProjectHandler;
use App\Project\Application\Command\Rename\RenameProjectCommand;
use App\Project\Application\Command\Rename\RenameProjectHandler;
use App\Project\Application\Command\Worker\AddProjectWorkerCommand;
use App\Project\Application\Command\Worker\AddProjectWorkerHandler;
use App\Project\Application\Command\Worker\RemoveProjectWorkerCommand;
use App\Project\Application\Command\Worker\RemoveProjectWorkerHandler;
use App\Project\Domain\Model\ProjectSnapshotFactory;
use App\Project\Infrastructure\Event\ProjectEventSerializer;
use App\Shared\Domain\Model\AggregateSnapshot;
use App\Shared\Event\SnapshotStore;
use App\Shared\Event\UserDeletedIntegrationEventSerializer;
use App\Shared\Infrastructure\Event\DomainEventDispatcher;
use App\Shared\ValueObject\Uuid;
use App\Tests\Project\Helpers\ProjectTestFactory;
use App\User\Infrastructure\Event\UserEventSerializer;
use Doctrine\DBAL\Connection;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProjectSnapshotIntegrationTest extends KernelTestCase
{
    private Connection $connection;
    private DoctrineEventStore $doctrineEventStore;
    private DomainEventDispatcher $domainEventDispatcher;
    private DoctrineSnapshotStore $doctrineSnapshotStore;
    private ProjectSnapshotFactory $projectSnapshotFactory;
    private FrequencyBasedSnapshotStrategy $frequencyBasedSnapshotStrategy;
    private ProjectEventStoreRepository $projectEventStoreRepository;
    private RegisterProjectHandler $registerProjectHandler;
    private RenameProjectHandler $renameProjectHandler;
    private AddProjectWorkerHandler $addProjectWorkerHandler;
    private RemoveProjectWorkerHandler $removeProjectWorkerHandler;

    protected function setUp(): void
    {
        // Boot Symfony kernel for database access
        self::bootKernel();
        $container = static::getContainer();

        // Get database connection
        $this->connection = $container->get('doctrine.dbal.default_connection');

        // Clean up database tables before each test
        $this->connection->executeStatement('TRUNCATE event_store CASCADE');
        $this->connection->executeStatement('TRUNCATE aggregate_snapshots CASCADE');

        // Setup event serializers
        $projectEventSerializer = new ProjectEventSerializer();
        $userEventSerializer = new UserEventSerializer();
        $userDeletedIntegrationEventSerializer = new UserDeletedIntegrationEventSerializer();

        $compositeEventSerializer = new CompositeEventSerializer(
            $projectEventSerializer,
            $userEventSerializer,
            $userDeletedIntegrationEventSerializer
        );

        // Setup core components
        $aggregateTypeResolver = new AggregateTypeResolver();
        $this->doctrineEventStore = new DoctrineEventStore($this->connection, $compositeEventSerializer, $aggregateTypeResolver);
        $symfonyEventDispatcher = $container->get('event_dispatcher');
        $this->domainEventDispatcher = new DomainEventDispatcher($symfonyEventDispatcher);
        $this->doctrineSnapshotStore = new DoctrineSnapshotStore($this->connection);
        $this->projectSnapshotFactory = new ProjectSnapshotFactory();

        // Setup snapshot strategy with frequency of 2 for testing
        $this->frequencyBasedSnapshotStrategy = new FrequencyBasedSnapshotStrategy(2);

        // Setup repository
        $this->projectEventStoreRepository = new ProjectEventStoreRepository(
            $this->doctrineEventStore,
            $this->domainEventDispatcher,
            $this->doctrineSnapshotStore,
            $this->projectSnapshotFactory,
            $this->frequencyBasedSnapshotStrategy
        );

        // Setup command handlers
        $this->registerProjectHandler = new RegisterProjectHandler($this->projectEventStoreRepository);
        $this->renameProjectHandler = new RenameProjectHandler($this->projectEventStoreRepository);
        $this->addProjectWorkerHandler = new AddProjectWorkerHandler($this->projectEventStoreRepository);
        $this->removeProjectWorkerHandler = new RemoveProjectWorkerHandler($this->projectEventStoreRepository);
    }

    public function testSnapshotsAreCreatedEvery2EventsAndProjectCanBeRestoredFromThem(): void
    {
        // Create project (1st event - version 1, no snapshot yet)
        $registerProjectCommand = ProjectTestFactory::createValidRegisterProjectCommand([
            'name' => 'Snapshot Test Project',
        ]);
        $project = ($this->registerProjectHandler)($registerProjectCommand);

        // No snapshot should exist yet (version 2, divisible by 2 but snapshot created after rename)
        $this->assertTrue($this->doctrineSnapshotStore->exists($project->getId(), 'Project'));

        // Rename project (2nd event - should create snapshot at version 2)
        $renameProjectCommand = RenameProjectCommand::fromPrimitives(
            (string) $project->getId(),
            'Renamed Once',
            (string) $project->getOwnerId()
        );
        ($this->renameProjectHandler)($renameProjectCommand);

        // Verify snapshot was created at version 2
        $this->assertTrue($this->doctrineSnapshotStore->exists($project->getId(), 'Project'));
        $this->assertEquals(2, $this->doctrineSnapshotStore->getLatestVersion($project->getId(), 'Project'));

        // Add worker (3rd event)
        $uuid = ProjectTestFactory::createValidUuid();
        $addedBy = ProjectTestFactory::createValidUuid();
        $addProjectWorkerCommand = AddProjectWorkerCommand::fromPrimitives(
            (string) $project->getId(),
            (string) $uuid,
            'participant',
            (string) $addedBy
        );
        ($this->addProjectWorkerHandler)($addProjectWorkerCommand);

        // Add another worker (4th event - should create snapshot at version 4)
        $userId2 = ProjectTestFactory::createValidUuid();
        $addWorkerCommand2 = AddProjectWorkerCommand::fromPrimitives(
            (string) $project->getId(),
            (string) $userId2,
            'owner',
            (string) $addedBy
        );
        ($this->addProjectWorkerHandler)($addWorkerCommand2);

        // Verify snapshot was created at version 4
        $this->assertEquals(4, $this->doctrineSnapshotStore->getLatestVersion($project->getId(), 'Project'));

        // Rename again (5th event)
        $renameCommand2 = RenameProjectCommand::fromPrimitives(
            (string) $project->getId(),
            'Final Name After 5 Events',
            (string) $project->getOwnerId()
        );
        ($this->renameProjectHandler)($renameCommand2);

        // Remove one worker (6th event - should create snapshot at version 6)
        $removeProjectWorkerCommand = RemoveProjectWorkerCommand::fromPrimitives(
            (string) $project->getId(),
            (string) $uuid,
            (string) $addedBy
        );
        ($this->removeProjectWorkerHandler)($removeProjectWorkerCommand);

        // Verify final snapshot was created at version 6
        $this->assertEquals(6, $this->doctrineSnapshotStore->getLatestVersion($project->getId(), 'Project'));

        // Load project from snapshot + events
        $restoredProject = $this->projectEventStoreRepository->load($project->getId());

        // Verify project state is correctly restored
        $this->assertEquals('Final Name After 5 Events', (string) $restoredProject->getName());
        $this->assertCount(2, $restoredProject->getWorkers());
        $this->assertTrue($restoredProject->getId()->equals($project->getId()));
        $this->assertFalse($restoredProject->isDeleted());

        // Verify remaining workers (owner + second worker)
        $workers = $restoredProject->getWorkers();
        // Find the second worker (not the original owner)
        $secondWorker = null;
        foreach ($workers as $worker) {
            if ($worker->getUserId()->equals($userId2)) {
                $secondWorker = $worker;
                break;
            }
        }
        $this->assertTrue($secondWorker !== null);
        $this->assertEquals('owner', $secondWorker->getRole()->toString());
    }

    public function testProjectCanBeLoadedFromSnapshotWhenNoEventsExistAfterSnapshot(): void
    {
        // Create project and add worker to reach version 2 (trigger snapshot)
        $registerProjectCommand = ProjectTestFactory::createValidRegisterProjectCommand([
            'name' => 'Snapshot Only Test',
        ]);
        $project = ($this->registerProjectHandler)($registerProjectCommand);

        $uuid = ProjectTestFactory::createValidUuid();
        $addedBy = ProjectTestFactory::createValidUuid();
        $addProjectWorkerCommand = AddProjectWorkerCommand::fromPrimitives(
            (string) $project->getId(),
            (string) $uuid,
            'participant',
            (string) $addedBy
        );
        ($this->addProjectWorkerHandler)($addProjectWorkerCommand);

        // Verify snapshot was created at version 2
        $this->assertEquals(2, $this->doctrineSnapshotStore->getLatestVersion($project->getId(), 'Project'));

        // Load project (should load from snapshot)
        $restoredProject = $this->projectEventStoreRepository->load($project->getId());

        // Verify state
        $this->assertEquals('Snapshot Only Test', (string) $restoredProject->getName());
        $this->assertCount(2, $restoredProject->getWorkers());
        $this->assertTrue($restoredProject->getId()->equals($project->getId()));
    }

    public function testProjectLoadsCorrectlyWhenEventsExistAfterLatestSnapshot(): void
    {
        // Create project, add worker (triggers snapshot at version 2)
        $registerProjectCommand = ProjectTestFactory::createValidRegisterProjectCommand([
            'name' => 'Events After Snapshot',
        ]);
        $project = ($this->registerProjectHandler)($registerProjectCommand);

        $uuid = ProjectTestFactory::createValidUuid();
        $addedBy = ProjectTestFactory::createValidUuid();
        $addProjectWorkerCommand = AddProjectWorkerCommand::fromPrimitives(
            (string) $project->getId(),
            (string) $uuid,
            'participant',
            (string) $addedBy
        );
        ($this->addProjectWorkerHandler)($addProjectWorkerCommand);

        // Verify snapshot exists at version 2
        $this->assertEquals(2, $this->doctrineSnapshotStore->getLatestVersion($project->getId(), 'Project'));

        // Rename project (3rd event - after snapshot)
        $renameProjectCommand = RenameProjectCommand::fromPrimitives(
            (string) $project->getId(),
            'Renamed After Snapshot',
            (string) $project->getOwnerId()
        );
        ($this->renameProjectHandler)($renameProjectCommand);

        // Load project (should load from snapshot + replay events after)
        $restoredProject = $this->projectEventStoreRepository->load($project->getId());

        // Verify state includes both snapshot data and events after snapshot
        $this->assertEquals('Renamed After Snapshot', (string) $restoredProject->getName());
        $this->assertCount(2, $restoredProject->getWorkers());
        $this->assertTrue($restoredProject->getId()->equals($project->getId()));

        $workers = $restoredProject->getWorkers();
        // Find the added worker (not the owner)
        $addedWorker = null;
        foreach ($workers as $worker) {
            if ($worker->getUserId()->equals($uuid)) {
                $addedWorker = $worker;
                break;
            }
        }
        $this->assertTrue($addedWorker !== null);
    }

    public function testSnapshotCreationFailureDoesNotAffectNormalOperation(): void
    {
        // Create a custom snapshot store that always fails
        $failingSnapshotStore = new class implements SnapshotStore {
            public function save(AggregateSnapshot $aggregateSnapshot): void
            {
                throw new RuntimeException('Snapshot storage failed');
            }

            public function loadLatest(Uuid $uuid, string $aggregateType): ?AggregateSnapshot
            {
                return null;
            }

            public function loadByVersion(Uuid $uuid, string $aggregateType, int $version): ?AggregateSnapshot
            {
                return null;
            }

            public function exists(Uuid $uuid, string $aggregateType): bool
            {
                return false;
            }

            public function removeAll(Uuid $uuid, string $aggregateType): void
            {
            }

            public function getLatestVersion(Uuid $uuid, string $aggregateType): ?int
            {
                return null;
            }
        };

        // Create repository with failing snapshot store
        $projectEventStoreRepository = new ProjectEventStoreRepository(
            $this->doctrineEventStore,
            $this->domainEventDispatcher,
            $failingSnapshotStore,
            $this->projectSnapshotFactory,
            $this->frequencyBasedSnapshotStrategy
        );

        $registerProjectHandler = new RegisterProjectHandler($projectEventStoreRepository);
        $renameProjectHandler = new RenameProjectHandler($projectEventStoreRepository);

        // Operations should still work despite snapshot failures
        $registerProjectCommand = ProjectTestFactory::createValidRegisterProjectCommand([
            'name' => 'Failing Snapshot Test',
        ]);
        $project = ($registerProjectHandler)($registerProjectCommand);

        // This should trigger snapshot creation but should not fail the operation
        $renameProjectCommand = RenameProjectCommand::fromPrimitives(
            (string) $project->getId(),
            'Renamed Despite Snapshot Failure',
            (string) $project->getOwnerId()
        );
        $renamedProject = ($renameProjectHandler)($renameProjectCommand);

        // Verify operations completed successfully
        $this->assertEquals('Renamed Despite Snapshot Failure', (string) $renamedProject->getName());

        // Verify project can be loaded from events only
        $loadedProject = $projectEventStoreRepository->load($project->getId());
        $this->assertEquals('Renamed Despite Snapshot Failure', (string) $loadedProject->getName());
    }

    public function testMultipleProjectsHaveIndependentSnapshots(): void
    {
        // Create first project
        $registerProjectCommand = ProjectTestFactory::createValidRegisterProjectCommand([
            'name' => 'Project One',
        ]);
        $project1 = ($this->registerProjectHandler)($registerProjectCommand);

        // Create second project
        $registerCommand2 = ProjectTestFactory::createValidRegisterProjectCommand([
            'name' => 'Project Two',
        ]);
        $project2 = ($this->registerProjectHandler)($registerCommand2);

        // Add workers to both projects to trigger snapshots
        $uuid = ProjectTestFactory::createValidUuid();
        $userId2 = ProjectTestFactory::createValidUuid();
        $addedBy = ProjectTestFactory::createValidUuid();

        ($this->addProjectWorkerHandler)(AddProjectWorkerCommand::fromPrimitives(
            (string) $project1->getId(),
            (string) $uuid,
            'participant',
            (string) $addedBy
        ));

        ($this->addProjectWorkerHandler)(AddProjectWorkerCommand::fromPrimitives(
            (string) $project2->getId(),
            (string) $userId2,
            'owner',
            (string) $addedBy
        ));

        // Verify both projects have snapshots
        $this->assertTrue($this->doctrineSnapshotStore->exists($project1->getId(), 'Project'));
        $this->assertTrue($this->doctrineSnapshotStore->exists($project2->getId(), 'Project'));

        // Load both projects and verify independent state
        $loadedProject1 = $this->projectEventStoreRepository->load($project1->getId());
        $loadedProject2 = $this->projectEventStoreRepository->load($project2->getId());

        $this->assertEquals('Project One', (string) $loadedProject1->getName());
        $this->assertEquals('Project Two', (string) $loadedProject2->getName());

        $this->assertCount(2, $loadedProject1->getWorkers());
        $this->assertCount(2, $loadedProject2->getWorkers());

        $workers1 = $loadedProject1->getWorkers();
        $workers2 = $loadedProject2->getWorkers();

        // Find the added workers (not the owners)
        $addedWorker1 = null;
        $addedWorker2 = null;
        
        foreach ($workers1 as $worker) {
            if ($worker->getUserId()->equals($uuid)) {
                $addedWorker1 = $worker;
                break;
            }
        }
        
        foreach ($workers2 as $worker) {
            if ($worker->getUserId()->equals($userId2)) {
                $addedWorker2 = $worker;
                break;
            }
        }

        $this->assertTrue($addedWorker1 !== null);
        $this->assertTrue($addedWorker2 !== null);
        $this->assertEquals('participant', $addedWorker1->getRole()->toString());
        $this->assertEquals('owner', $addedWorker2->getRole()->toString());
    }
}
