<?php

declare(strict_types=1);

namespace App\Tests\Unit\Project\Domain;

use App\Project\Domain\Model\Project;
use App\Project\Domain\Model\ProjectSnapshot;
use App\Project\Domain\Model\ProjectSnapshotFactory;
use App\Project\Domain\ValueObject\ProjectName;
use App\Project\Domain\ValueObject\ProjectRole;
use App\Project\Domain\ValueObject\ProjectWorker;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ProjectSnapshotTest extends TestCase
{
    private ProjectSnapshotFactory $snapshotFactory;

    protected function setUp(): void
    {
        $this->snapshotFactory = new ProjectSnapshotFactory();
    }

    public function testCreateSnapshotFromProject(): void
    {
        // Arrange - Create a project with some data
        $projectId = Uuid::generate();
        $ownerId = Uuid::generate();
        $projectName = new ProjectName('Test Project');
        
        $project = Project::create($projectName, $ownerId);
        
        // Add some workers
        $worker1Id = Uuid::generate();
        $worker2Id = Uuid::generate();
        $addedBy = Uuid::generate();
        
        $worker1 = ProjectWorker::create($worker1Id, ProjectRole::participant(), $addedBy);
        $worker2 = ProjectWorker::create($worker2Id, ProjectRole::owner(), $addedBy);
        
        $project->addWorker($worker1);
        $project->addWorker($worker2);
        
        $version = 5;

        // Act - Create snapshot
        $snapshot = $this->snapshotFactory->createSnapshot($project, $version);

        // Assert
        $this->assertInstanceOf(ProjectSnapshot::class, $snapshot);
        $this->assertEquals($project->getId(), $snapshot->getAggregateId());
        $this->assertEquals($version, $snapshot->getVersion());
        
        $data = $snapshot->getData();
        $this->assertEquals($project->getId()->toString(), $data['id']);
        $this->assertEquals('Test Project', $data['name']);
        $this->assertEquals($ownerId->toString(), $data['ownerId']);
        $this->assertArrayHasKey('createdAt', $data);
        $this->assertNull($data['deletedAt']);
        $this->assertCount(2, $data['workers']);
        
        // Check worker data
        $this->assertEquals($worker1Id->toString(), $data['workers'][0]['userId']);
        $this->assertEquals('participant', $data['workers'][0]['role']);
        $this->assertEquals($addedBy->toString(), $data['workers'][0]['addedBy']);
        
        $this->assertEquals($worker2Id->toString(), $data['workers'][1]['userId']);
        $this->assertEquals('owner', $data['workers'][1]['role']);
        $this->assertEquals($addedBy->toString(), $data['workers'][1]['addedBy']);
    }

    public function testRestoreProjectFromSnapshot(): void
    {
        // Arrange - Create original project
        $projectId = Uuid::generate();
        $ownerId = Uuid::generate();
        $projectName = new ProjectName('Restored Project');
        
        $originalProject = Project::create($projectName, $ownerId);
        
        // Add worker to original
        $workerId = Uuid::generate();
        $addedBy = Uuid::generate();
        $worker = ProjectWorker::create($workerId, ProjectRole::participant(), $addedBy);
        $originalProject->addWorker($worker);
        
        // Create snapshot
        $version = 3;
        $snapshot = $this->snapshotFactory->createSnapshot($originalProject, $version);

        // Act - Restore from snapshot
        $restoredProject = $this->snapshotFactory->restoreFromSnapshot($snapshot);

        // Assert - Compare restored project with original
        $this->assertEquals($originalProject->getId(), $restoredProject->getId());
        $this->assertEquals((string) $originalProject->getName(), (string) $restoredProject->getName());
        $this->assertEquals($originalProject->getOwnerId(), $restoredProject->getOwnerId());
        
        // Compare timestamps without microseconds (lost in serialization)
        $this->assertEquals(
            $originalProject->getCreatedAt()->format('Y-m-d H:i:s'),
            $restoredProject->getCreatedAt()->format('Y-m-d H:i:s')
        );
        
        $this->assertEquals($originalProject->getDeletedAt(), $restoredProject->getDeletedAt());
        $this->assertEquals($version, $restoredProject->getVersion());
        
        // Check workers
        $restoredWorkers = $restoredProject->getWorkers();
        $originalWorkers = $originalProject->getWorkers();
        
        $this->assertCount(count($originalWorkers), $restoredWorkers);
        
        foreach ($originalWorkers as $index => $originalWorker) {
            $restoredWorker = $restoredWorkers[$index];
            $this->assertEquals($originalWorker->getUserId(), $restoredWorker->getUserId());
            $this->assertEquals($originalWorker->getRole(), $restoredWorker->getRole());
            $this->assertEquals($originalWorker->getAddedBy(), $restoredWorker->getAddedBy());
            // Compare worker timestamps without microseconds
            $this->assertEquals(
                $originalWorker->getCreatedAt()->format('Y-m-d H:i:s'),
                $restoredWorker->getCreatedAt()->format('Y-m-d H:i:s')
            );
        }
    }

    public function testSnapshotWithDeletedProject(): void
    {
        // Arrange
        $projectId = Uuid::generate();
        $ownerId = Uuid::generate();
        $projectName = new ProjectName('Deleted Project');
        
        $project = Project::create($projectName, $ownerId);
        $project->delete();
        
        $version = 2;

        // Act
        $snapshot = $this->snapshotFactory->createSnapshot($project, $version);

        // Assert
        $data = $snapshot->getData();
        $this->assertNotNull($data['deletedAt']);
        
        // Restore and verify
        $restoredProject = $this->snapshotFactory->restoreFromSnapshot($snapshot);
        $this->assertTrue($restoredProject->isDeleted());
        
        // Compare only the timestamp part (ignore microseconds differences)
        $this->assertEquals(
            $project->getDeletedAt()->format('Y-m-d H:i:s'),
            $restoredProject->getDeletedAt()->format('Y-m-d H:i:s')
        );
    }

    public function testSnapshotWithNoWorkers(): void
    {
        // Arrange
        $projectId = Uuid::generate();
        $ownerId = Uuid::generate();
        $projectName = new ProjectName('Empty Project');
        
        $project = Project::create($projectName, $ownerId);
        $version = 1;

        // Act
        $snapshot = $this->snapshotFactory->createSnapshot($project, $version);

        // Assert
        $data = $snapshot->getData();
        $this->assertEmpty($data['workers']);
        
        // Restore and verify
        $restoredProject = $this->snapshotFactory->restoreFromSnapshot($snapshot);
        $this->assertEmpty($restoredProject->getWorkers());
    }

    public function testSnapshotDataSerialization(): void
    {
        // Arrange
        $projectId = Uuid::generate();
        $ownerId = Uuid::generate();
        $projectName = new ProjectName('Serialization Test');
        
        $project = Project::create($projectName, $ownerId);
        $version = 1;

        // Act
        $snapshot = $this->snapshotFactory->createSnapshot($project, $version);
        $jsonData = json_encode($snapshot->getData());
        $decodedData = json_decode($jsonData, true);

        // Assert - Verify data can be serialized and deserialized
        $this->assertIsString($jsonData);
        $this->assertIsArray($decodedData);
        $this->assertEquals($project->getId()->toString(), $decodedData['id']);
        $this->assertEquals((string) $projectName, $decodedData['name']);
        $this->assertEquals($ownerId->toString(), $decodedData['ownerId']);
    }
}