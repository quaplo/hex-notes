<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Persistence\EventStore;

use App\Infrastructure\Persistence\Doctrine\Entity\EventStoreEntity;
use App\Project\Domain\Event\ProjectCreatedEvent;
use App\Project\Domain\ValueObject\ProjectName;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CompoundPrimaryKeyTest extends TestCase
{
    public function testCompoundPrimaryKeyStructure(): void
    {
        $aggregateId = Uuid::generate();
        $version = 1;
        $aggregateType = 'App\\Project';
        $eventType = ProjectCreatedEvent::class;
        $eventData = '{"test": "data"}';
        $occurredAt = new DateTimeImmutable();

        $entity = new EventStoreEntity(
            $aggregateId->toString(),
            $version,
            $aggregateType,
            $eventType,
            $eventData,
            $occurredAt
        );

        // Compound primary key components
        $this->assertEquals($aggregateId->toString(), $entity->getAggregateId());
        $this->assertEquals($version, $entity->getVersion());

        // Other properties
        $this->assertEquals($aggregateType, $entity->getAggregateType());
        $this->assertEquals($eventType, $entity->getEventType());
        $this->assertEquals($eventData, $entity->getEventData());
        $this->assertEquals($occurredAt, $entity->getOccurredAt());
    }

    public function testUniqueCompoundPrimaryKey(): void
    {
        $aggregateId = Uuid::generate();
        $occurredAt = new DateTimeImmutable();

        // Same aggregate, different versions should be unique
        $entity1 = new EventStoreEntity(
            $aggregateId->toString(),
            1,
            'App\\Project',
            ProjectCreatedEvent::class,
            '{"test": "data1"}',
            $occurredAt
        );

        $entity2 = new EventStoreEntity(
            $aggregateId->toString(),
            2,
            'App\\Project',
            ProjectCreatedEvent::class,
            '{"test": "data2"}',
            $occurredAt
        );

        // Should be different entities (different compound PK)
        $this->assertEquals($entity1->getAggregateId(), $entity2->getAggregateId());
        $this->assertNotEquals($entity1->getVersion(), $entity2->getVersion());
    }

    public function testDifferentAggregatesSameVersion(): void
    {
        $aggregateId1 = Uuid::generate();
        $aggregateId2 = Uuid::generate();
        $version = 1;
        $occurredAt = new DateTimeImmutable();

        // Different aggregates, same version should be unique
        $entity1 = new EventStoreEntity(
            $aggregateId1->toString(),
            $version,
            'App\\Project',
            ProjectCreatedEvent::class,
            '{"test": "data1"}',
            $occurredAt
        );

        $entity2 = new EventStoreEntity(
            $aggregateId2->toString(),
            $version,
            'App\\Order',
            'App\\Order\\Domain\\Event\\OrderCreatedEvent',
            '{"test": "data2"}',
            $occurredAt
        );

        // Should be different entities (different compound PK)
        $this->assertNotEquals($entity1->getAggregateId(), $entity2->getAggregateId());
        $this->assertEquals($entity1->getVersion(), $entity2->getVersion());
        $this->assertNotEquals($entity1->getAggregateType(), $entity2->getAggregateType());
    }

    public function testNoArtificialIdNeeded(): void
    {
        $entity = new EventStoreEntity(
            Uuid::generate()->toString(),
            1,
            'App\\Project',
            ProjectCreatedEvent::class,
            '{"test": "data"}',
            new DateTimeImmutable()
        );

        // Verify no getId() method exists (removed artificial ID)
        $this->assertFalse(method_exists($entity, 'getId'));

        // Natural compound key provides identification
        $this->assertTrue(method_exists($entity, 'getAggregateId'));
        $this->assertTrue(method_exists($entity, 'getVersion'));
    }
}
