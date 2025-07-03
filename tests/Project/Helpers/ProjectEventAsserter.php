<?php

declare(strict_types=1);

namespace App\Tests\Project\Helpers;

use App\Project\Domain\Event\ProjectCreatedEvent;
use App\Project\Domain\Event\ProjectDeletedEvent;
use App\Project\Domain\Event\ProjectRenamedEvent;
use App\Project\Domain\Event\ProjectWorkerAddedEvent;
use App\Project\Domain\Event\ProjectWorkerRemovedEvent;
use App\Project\Domain\ValueObject\ProjectName;
use App\Project\Domain\ValueObject\ProjectRole;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;
use PHPUnit\Framework\Assert;

final class ProjectEventAsserter
{
    public static function assertProjectCreatedEvent(
        DomainEvent $event, 
        Uuid $expectedId, 
        ProjectName $expectedName,
        Uuid $expectedOwnerId
    ): void {
        Assert::assertInstanceOf(ProjectCreatedEvent::class, $event);
        /** @var ProjectCreatedEvent $event */
        
        Assert::assertTrue($event->getProjectId()->equals($expectedId));
        Assert::assertEquals((string)$expectedName, (string)$event->getName());
        Assert::assertTrue($event->getOwnerId()->equals($expectedOwnerId));
        Assert::assertInstanceOf(\DateTimeImmutable::class, $event->getOccurredAt());
    }

    public static function assertProjectRenamedEvent(
        DomainEvent $event, 
        Uuid $expectedId,
        ProjectName $expectedOldName, 
        ProjectName $expectedNewName
    ): void {
        Assert::assertInstanceOf(ProjectRenamedEvent::class, $event);
        /** @var ProjectRenamedEvent $event */
        
        Assert::assertTrue($event->getProjectId()->equals($expectedId));
        Assert::assertEquals((string)$expectedOldName, (string)$event->getOldName());
        Assert::assertEquals((string)$expectedNewName, (string)$event->getNewName());
        Assert::assertInstanceOf(\DateTimeImmutable::class, $event->getOccurredAt());
    }

    public static function assertProjectDeletedEvent(DomainEvent $event, Uuid $expectedId): void
    {
        Assert::assertInstanceOf(ProjectDeletedEvent::class, $event);
        /** @var ProjectDeletedEvent $event */
        
        Assert::assertTrue($event->getProjectId()->equals($expectedId));
        Assert::assertInstanceOf(\DateTimeImmutable::class, $event->getOccurredAt());
    }

    public static function assertProjectWorkerAddedEvent(
        DomainEvent $event,
        Uuid $expectedProjectId,
        Uuid $expectedUserId,
        ProjectRole $expectedRole,
        ?Uuid $expectedAddedBy = null
    ): void {
        Assert::assertInstanceOf(ProjectWorkerAddedEvent::class, $event);
        /** @var ProjectWorkerAddedEvent $event */
        
        Assert::assertTrue($event->getProjectId()->equals($expectedProjectId));
        Assert::assertTrue($event->getUserId()->equals($expectedUserId));
        Assert::assertEquals((string)$expectedRole, (string)$event->getRole());
        
        if ($expectedAddedBy !== null) {
            Assert::assertTrue($event->getAddedBy()->equals($expectedAddedBy));
        }
        
        Assert::assertInstanceOf(\DateTimeImmutable::class, $event->getOccurredAt());
    }

    public static function assertProjectWorkerRemovedEvent(
        DomainEvent $event,
        Uuid $expectedProjectId,
        Uuid $expectedUserId,
        ?Uuid $expectedRemovedBy = null
    ): void {
        Assert::assertInstanceOf(ProjectWorkerRemovedEvent::class, $event);
        /** @var ProjectWorkerRemovedEvent $event */
        
        Assert::assertTrue($event->getProjectId()->equals($expectedProjectId));
        Assert::assertTrue($event->getUserId()->equals($expectedUserId));
        
        if ($expectedRemovedBy !== null) {
            Assert::assertTrue($event->getRemovedBy()->equals($expectedRemovedBy));
        }
        
        Assert::assertInstanceOf(\DateTimeImmutable::class, $event->getOccurredAt());
    }

    public static function assertEventCount(array $events, int $expectedCount): void
    {
        Assert::assertCount($expectedCount, $events, "Expected {$expectedCount} events, got " . count($events));
    }

    public static function assertContainsEventType(array $events, string $eventClass): void
    {
        $found = false;
        foreach ($events as $event) {
            if ($event instanceof $eventClass) {
                $found = true;
                break;
            }
        }
        
        Assert::assertTrue($found, "Expected to find event of type {$eventClass} in events");
    }

    public static function assertDoesNotContainEventType(array $events, string $eventClass): void
    {
        foreach ($events as $event) {
            Assert::assertNotInstanceOf($eventClass, $event, "Did not expect to find event of type {$eventClass}");
        }
    }

    public static function assertEventsInOrder(array $events, array $expectedEventClasses): void
    {
        Assert::assertCount(count($expectedEventClasses), $events, "Event count mismatch");
        
        foreach ($events as $index => $event) {
            $expectedClass = $expectedEventClasses[$index];
            Assert::assertInstanceOf($expectedClass, $event, "Event at index {$index} should be {$expectedClass}");
        }
    }
}