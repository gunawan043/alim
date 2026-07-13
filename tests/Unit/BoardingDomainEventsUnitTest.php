<?php

namespace Tests\Unit;

use App\Domain\Events\BoardingPermitDecided;
use App\Domain\Events\BoardingPermitSubmitted;
use App\Domain\Events\BoardingVisitCheckIn;
use App\Domain\Events\BoardingVisitDecided;
use App\Domain\Listeners\RecordBoardingPermitTimeline;
use App\Domain\Listeners\RecordBoardingVisitTimeline;
use App\Models\BoardingTimelineEvent;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

use ReflectionClass;

/**
 * Unit-style tests for the boarding domain events/listeners.
 *
 * Verifies the public API surface of the refactor: events expose aggregates
 * and decision constants, listeners expose the expected handler methods, and
 * the timeline model defines all event-type constants.
 */
class BoardingDomainEventsUnitTest extends PHPUnitTestCase
{
    /** @test */
    public function events_class_loads_with_correct_dependencies(): void
    {
        $this->assertTrue(class_exists(BoardingPermitSubmitted::class));
        $this->assertTrue(class_exists(BoardingPermitDecided::class));
        $this->assertTrue(class_exists(BoardingVisitDecided::class));
        $this->assertTrue(class_exists(BoardingVisitCheckIn::class));
    }

    /** @test */
    public function permit_decided_event_decision_constants(): void
    {
        $this->assertEquals('approved', BoardingPermitDecided::APPROVED);
        $this->assertEquals('rejected', BoardingPermitDecided::REJECTED);
    }

    /** @test */
    public function visit_decided_event_decision_constants(): void
    {
        $this->assertEquals('approved', BoardingVisitDecided::APPROVED);
        $this->assertEquals('rejected', BoardingVisitDecided::REJECTED);
    }

    /** @test */
    public function permit_listener_has_required_methods_and_constructor_dep(): void
    {
        $reflection = new ReflectionClass(RecordBoardingPermitTimeline::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = $constructor->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('App\Domain\Services\BoardingTimelineService', (string) $params[0]->getType());

        $this->assertTrue($reflection->hasMethod('onSubmitted'));
        $this->assertTrue($reflection->hasMethod('onDecided'));
    }

    /** @test */
    public function visit_listener_has_required_methods_and_constructor_dep(): void
    {
        $reflection = new ReflectionClass(RecordBoardingVisitTimeline::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = $constructor->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('App\Domain\Services\BoardingTimelineService', (string) $params[0]->getType());

        $this->assertTrue($reflection->hasMethod('onDecided'));
        $this->assertTrue($reflection->hasMethod('onCheckIn'));
    }

    /** @test */
    public function timeline_event_type_constants_exist(): void
    {
        $this->assertNotEmpty(BoardingTimelineEvent::TYPE_CHECK_IN ?? null);
        $this->assertNotEmpty(BoardingTimelineEvent::TYPE_CHECK_OUT ?? null);
        $this->assertNotEmpty(BoardingTimelineEvent::TYPE_ROOM_TRANSFER ?? null);
        $this->assertNotEmpty(BoardingTimelineEvent::TYPE_LEAVE_APPROVED ?? null);
        $this->assertNotEmpty(BoardingTimelineEvent::TYPE_LEAVE_STARTED ?? null);
        $this->assertNotEmpty(BoardingTimelineEvent::TYPE_LEAVE_OVERDUE ?? null);
        $this->assertNotEmpty(BoardingTimelineEvent::TYPE_RETURNED ?? null);
        $this->assertNotEmpty(BoardingTimelineEvent::TYPE_HOSPITALIZED ?? null);
        $this->assertNotEmpty(BoardingTimelineEvent::TYPE_RECOVERED ?? null);
        $this->assertNotEmpty(BoardingTimelineEvent::TYPE_VISIT_APPROVED ?? null);
        $this->assertNotEmpty(BoardingTimelineEvent::TYPE_VISIT_REJECTED ?? null);
        $this->assertNotEmpty(BoardingTimelineEvent::TYPE_VISIT_CHECK_IN ?? null);
        $this->assertNotEmpty(BoardingTimelineEvent::TYPE_VISIT_CHECK_OUT ?? null);
        $this->assertNotEmpty(BoardingTimelineEvent::TYPE_VIOLATION ?? null);
        $this->assertNotEmpty(BoardingTimelineEvent::TYPE_REWARD ?? null);
        $this->assertNotEmpty(BoardingTimelineEvent::TYPE_EXPELLED ?? null);
        $this->assertNotEmpty(BoardingTimelineEvent::TYPE_TRANSFER ?? null);
        $this->assertNotEmpty(BoardingTimelineEvent::TYPE_HOLIDAY ?? null);
        $this->assertNotEmpty(BoardingTimelineEvent::TYPE_PERMIT_REJECTED ?? null);
    }
}