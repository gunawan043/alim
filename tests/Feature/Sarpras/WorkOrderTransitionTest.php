<?php

namespace Tests\Feature\Sarpras;

use App\Services\Sarpras\StateMachineRegistry;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Tests\Traits\CreatesSarprasFixtures;

class WorkOrderTransitionTest extends TestCase
{
    use CreatesSarprasFixtures;

    protected static $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$migrated) {
            Artisan::call('migrate:fresh', ['--force' => true]);
            self::$migrated = true;
        }
    }

    /** @test */
    public function asset_can_move_from_active_to_borrowed()
    {
        $this->createAsset();
        $this->assertTrue(StateMachineRegistry::canTransition(
            StateMachineRegistry::ASSET_STATUS, 'active', 'borrowed'
        ));
    }

    /** @test */
    public function asset_disposal_path_supported()
    {
        $this->createAsset();
        $this->assertTrue(StateMachineRegistry::canTransition(
            StateMachineRegistry::ASSET_STATUS, 'damaged', 'disposed'
        ));
    }

    /** @test */
    public function multiple_transitions_supported()
    {
        $transitions = StateMachineRegistry::transitionsFor(StateMachineRegistry::WORK_ORDER);
        $this->assertIsArray($transitions);
        $this->assertNotEmpty($transitions);
    }

    /** @test */
    public function invalid_transition_returns_false()
    {
        $this->assertFalse(StateMachineRegistry::canTransition(
            StateMachineRegistry::WORK_ORDER, 'closed', 'created'
        ));
    }

    /** @test */
    public function state_machine_handles_all_documented_states()
    {
        $transitions = StateMachineRegistry::transitionsFor(StateMachineRegistry::ASSET_STATUS);
        $this->assertArrayHasKey('active', $transitions);
        $this->assertArrayHasKey('borrowed', $transitions);
        $this->assertArrayHasKey('damaged', $transitions);
        $this->assertArrayHasKey('disposed', $transitions);
    }
}