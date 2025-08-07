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
use DateTimeImmutable;
use PHPUnit\Framework\Assert;

final class ProjectEventAsserter
{
    public static function assertProjectCreatedEvent(
        DomainEvent $domainEvent,
        Uuid $expectedId,
        ProjectName $projectName,
        Uuid $expectedOwnerId,
    ): void {
        Assert::assertInstanceOf(ProjectCreatedEvent::class, $domainEvent);
        Assert::assertTrue($domainEvent->getProjectId()->equals($expectedId));
        Assert::assertEquals((string) $projectName, (string) $domainEvent->getName());
        Assert::assertTrue($domainEvent->getOwnerId()->equals($expectedOwnerId));
        Assert::assertInstanceOf(DateTimeImmutable::class, $domainEvent->getOccurredAt());
    }

    public static function assertProjectRenamedEvent(
        DomainEvent $domainEvent,
        Uuid $uuid,
        ProjectName $expectedOldName,
        ProjectName $expectedNewName,
    ): void {
        Assert::assertInstanceOf(ProjectRenamedEvent::class, $domainEvent);
        Assert::assertTrue($domainEvent->getProjectId()->equals($uuid));
        Assert::assertEquals((string) $expectedOldName, (string) $domainEvent->getOldName());
        Assert::assertEquals((string) $expectedNewName, (string) $domainEvent->getNewName());
        Assert::assertInstanceOf(DateTimeImmutable::class, $domainEvent->getOccurredAt());
    }

    public static function assertProjectDeletedEvent(DomainEvent $domainEvent, Uuid $uuid): void
    {
        Assert::assertInstanceOf(ProjectDeletedEvent::class, $domainEvent);
        Assert::assertTrue($domainEvent->getProjectId()->equals($uuid));
        Assert::assertInstanceOf(DateTimeImmutable::class, $domainEvent->getOccurredAt());
    }

    public static function assertProjectWorkerAddedEvent(
        DomainEvent $domainEvent,
        Uuid $expectedProjectId,
        Uuid $expectedUserId,
        ProjectRole $projectRole,
        ?Uuid $expectedAddedBy = null,
    ): void {
        Assert::assertInstanceOf(ProjectWorkerAddedEvent::class, $domainEvent);
        Assert::assertTrue($domainEvent->getProjectId()->equals($expectedProjectId));
        Assert::assertTrue($domainEvent->getUserId()->equals($expectedUserId));
        Assert::assertEquals($projectRole->toString(), $domainEvent->getRole()->toString());

        if ($expectedAddedBy instanceof Uuid) {
            Assert::assertTrue($domainEvent->getAddedBy()->equals($expectedAddedBy));
        }

        Assert::assertInstanceOf(DateTimeImmutable::class, $domainEvent->getOccurredAt());
    }

    public static function assertProjectWorkerRemovedEvent(
        DomainEvent $domainEvent,
        Uuid $expectedProjectId,
        Uuid $expectedUserId,
        ?Uuid $expectedRemovedBy = null,
    ): void {
        Assert::assertInstanceOf(ProjectWorkerRemovedEvent::class, $domainEvent);
        Assert::assertTrue($domainEvent->getProjectId()->equals($expectedProjectId));
        Assert::assertTrue($domainEvent->getUserId()->equals($expectedUserId));

        if ($expectedRemovedBy instanceof Uuid) {
            Assert::assertTrue($domainEvent->getRemovedBy()->equals($expectedRemovedBy));
        }

        Assert::assertInstanceOf(DateTimeImmutable::class, $domainEvent->getOccurredAt());
    }

    public static function assertEventCount(array $events, int $expectedCount): void
    {
        Assert::assertCount($expectedCount, $events, "Expected {$expectedCount} events, got ".\count($events));
    }

    public static function assertContainsEventType(array $events, string $eventClass): void
    {
        $found = array_any($events, fn ($event): bool => $event instanceof $eventClass);
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
        Assert::assertCount(\count($expectedEventClasses), $events, 'Event count mismatch');

        foreach ($events as $index => $event) {
            $expectedClass = $expectedEventClasses[$index];
            Assert::assertInstanceOf($expectedClass, $event, "Event at index {$index} should be {$expectedClass}");
        }
    }
}
