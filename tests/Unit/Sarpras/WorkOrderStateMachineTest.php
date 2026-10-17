<?php

namespace Tests\Unit\Sarpras;

use App\Services\Sarpras\IllegalStateTransitionException;
use App\Services\Sarpras\StateMachine;
use App\Services\Sarpras\StateMachineRegistry;
use Tests\TestCase;

class WorkOrderStateMachineTest extends TestCase
{
    /** @test */
    public function registry_can_transition_work_order_created_to_assigned()
    {
        $this->assertTrue(StateMachineRegistry::canTransition(
            StateMachineRegistry::WORK_ORDER, 'created', 'assigned'
        ));
    }

    /** @test */
    public function registry_can_transition_work_order_accepted_to_working()
    {
        $this->assertTrue(StateMachineRegistry::canTransition(
            StateMachineRegistry::WORK_ORDER, 'accepted', 'working'
        ));
    }

    /** @test */
    public function registry_rejects_invalid_work_order_transition()
    {
        $this->assertFalse(StateMachineRegistry::canTransition(
            StateMachineRegistry::WORK_ORDER, 'created', 'closed'
        ));
    }

    /** @test */
    public function asset_status_can_move_from_active_to_borrowed()
    {
        $this->assertTrue(StateMachineRegistry::canTransition(
            StateMachineRegistry::ASSET_STATUS, 'active', 'borrowed'
        ));
    }

    /** @test */
    public function asset_status_can_move_from_damaged_to_under_repair()
    {
        $this->assertTrue(StateMachineRegistry::canTransition(
            StateMachineRegistry::ASSET_STATUS, 'damaged', 'under_repair'
        ));
    }

    /** @test */
    public function disposed_state_is_terminal()
    {
        $this->assertTrue(empty(StateMachineRegistry::transitionsFor(StateMachineRegistry::ASSET_STATUS)['disposed']));
    }

    /** @test */
    public function asset_cannot_move_from_borrowed_to_disposed_directly()
    {
        $this->assertFalse(StateMachineRegistry::canTransition(
            StateMachineRegistry::ASSET_STATUS, 'borrowed', 'disposed'
        ));
    }

    /** @test */
    public function maintenance_schedule_transitions()
    {
        $this->assertTrue(StateMachineRegistry::canTransition(
            StateMachineRegistry::MAINTENANCE_SCHEDULE, 'active', 'paused'
        ));
        $this->assertTrue(StateMachineRegistry::canTransition(
            StateMachineRegistry::MAINTENANCE_SCHEDULE, 'paused', 'active'
        ));
    }

    /** @test */
    public function illegal_transition_throws_exception()
    {
        $this->expectException(IllegalStateTransitionException::class);
        StateMachineRegistry::assertValidTransition(
            StateMachineRegistry::REPAIR_REQUEST, 'closed', 'draft'
        );
    }

    /** @test */
    public function state_machine_singleton_registers_all_models()
    {
        $sm = app(StateMachine::class);
        foreach ([StateMachineRegistry::WORK_ORDER, StateMachineRegistry::ASSET_STATUS, StateMachineRegistry::MAINTENANCE_SCHEDULE] as $model) {
            $transitions = StateMachineRegistry::transitionsFor($model);
            $this->assertNotEmpty($transitions, "Model {$model} should have transitions defined");
        }
    }
}
