<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Persistence\EventStore;

use App\Infrastructure\Persistence\EventStore\AggregateTypeResolver;
use App\Project\Domain\Event\ProjectCreatedEvent;
use App\Shared\Domain\Event\UserDeletedIntegrationEvent;
use App\User\Domain\Event\UserDeletedEvent;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AggregateTypeResolverTest extends TestCase
{
    private AggregateTypeResolver $aggregateTypeResolver;

    protected function setUp(): void
    {
        $this->aggregateTypeResolver = new AggregateTypeResolver();
    }

    public function testResolveFromProjectEvent(): void
    {
        $aggregateType = $this->aggregateTypeResolver->resolveFromClassName(ProjectCreatedEvent::class);

        $this->assertEquals('App\\Project', $aggregateType);
    }

    public function testResolveFromUserEvent(): void
    {
        $aggregateType = $this->aggregateTypeResolver->resolveFromClassName(UserDeletedEvent::class);

        $this->assertEquals('App\\User', $aggregateType);
    }

    public function testResolveFromSharedEvent(): void
    {
        $aggregateType = $this->aggregateTypeResolver->resolveFromClassName(UserDeletedIntegrationEvent::class);

        $this->assertEquals('App\\Shared', $aggregateType);
    }

    public function testResolveFromFutureOrderEvent(): void
    {
        // Simulácia budúcej Order domény
        $aggregateType = $this->aggregateTypeResolver->resolveFromClassName('App\\Order\\Domain\\Event\\OrderCreatedEvent');

        $this->assertEquals('App\\Order', $aggregateType);
    }

    public function testResolveFromInvalidNamespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid event class namespace structure: InvalidEvent');

        $this->aggregateTypeResolver->resolveFromClassName('InvalidEvent');
    }

    public function testResolveFromShortNamespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid event class namespace structure: App\\Event');

        $this->aggregateTypeResolver->resolveFromClassName('App\\Event');
    }
}
