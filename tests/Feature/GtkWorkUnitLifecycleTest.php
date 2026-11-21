<?php

namespace Tests\Feature;

use App\Exceptions\PlacementLifecycleException;
use App\Http\Controllers\GtkWizardController;
use App\Models\GtkEmployment;
use App\Models\GtkWorkUnit;
use App\Models\GtkWorkUnitPeriod;
use App\Models\StructuralPosition;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\GtkPlacementLifecycleService;
use App\Services\GtkPositionAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use ReflectionMethod;
use Tests\TestCase;

class GtkWorkUnitLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Lifecycle User '.Str::random(6),
            'email' => Str::uuid().'@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
    }

    private function makeUnit(string $label = 'A'): WorkUnit
    {
        return WorkUnit::create([
            'name' => 'Unit '.$label.' '.Str::random(5),
            'code' => 'WU-'.$label.'-'.Str::upper(Str::random(6)),
            'type' => 'Unit Akademik',
            'is_active' => true,
        ]);
    }

    private function makePosition(): StructuralPosition
    {
        return StructuralPosition::create([
            'code' => 'guru-'.Str::lower(Str::random(8)),
            'name' => 'Guru',
            'level' => 'pondok',
            'hierarchy_level' => 5,
            'is_active' => true,
        ]);
    }

    private function makeEmployment(User $user): GtkEmployment
    {
        return GtkEmployment::create([
            'user_id' => $user->id,
            'status_kepegawaian' => 'GTY',
        ]);
    }

    private function placement(User $user, WorkUnit $unit, bool $primary = true): GtkWorkUnit
    {
        return GtkWorkUnit::create([
            'user_id' => $user->id,
            'work_unit_id' => $unit->id,
            'is_primary' => $primary,
        ]);
    }

    private function invokeSyncWorkUnit(GtkWizardController $controller, string $userId, string $unitId, ?string $jabatan = null): void
    {
        $method = new ReflectionMethod($controller, 'syncWorkUnit');
        $method->setAccessible(true);
        $method->invoke($controller, $userId, $unitId, $jabatan);
    }

    public function test_transfer_does_not_change_old_row_work_unit_id(): void
    {
        config()->set('gtk_work_unit.lifecycle.enabled', true);

        $user = $this->makeUser();
        $unitA = $this->makeUnit('A');
        $unitB = $this->makeUnit('B');
        $old = $this->placement($user, $unitA);
        $oldPeriod = app(GtkPlacementLifecycleService::class)->start($user, $unitA, null, '2026-01-01');
        $oldId = $old->id;
        $oldRowId = $old->id;

        $controller = app(GtkWizardController::class);
        $this->invokeSyncWorkUnit($controller, $user->id, $unitB->id, 'Guru');

        $old->refresh();

        $this->assertSame($oldId, $oldRowId);
        $this->assertSame($oldId, $old->id);
        $this->assertSame($unitA->id, $old->work_unit_id);
        $this->assertFalse($old->is_primary);

        $this->assertDatabaseHas('gtk_work_unit_periods', [
            'id' => $oldPeriod->id,
            'status' => GtkWorkUnitPeriod::STATUS_ENDED,
            'user_id' => $user->id,
            'work_unit_id' => $unitA->id,
        ]);
    }

    public function test_transfer_closes_old_period_and_starts_destination(): void
    {
        config()->set('gtk_work_unit.lifecycle.enabled', true);

        $user = $this->makeUser();
        $unitA = $this->makeUnit('A');
        $unitB = $this->makeUnit('B');
        $this->placement($user, $unitA);
        app(GtkPlacementLifecycleService::class)->start($user, $unitA, null, '2026-01-01');

        $controller = app(GtkWizardController::class);
        $this->invokeSyncWorkUnit($controller, $user->id, $unitB->id, 'Guru');

        $this->assertDatabaseHas('gtk_work_unit', [
            'user_id' => $user->id,
            'work_unit_id' => $unitB->id,
            'is_primary' => true,
        ]);

        $this->assertSame(1, GtkWorkUnitPeriod::where('user_id', $user->id)
            ->where('work_unit_id', $unitA->id)
            ->where('status', GtkWorkUnitPeriod::STATUS_ENDED)
            ->count());

        $this->assertSame(1, GtkWorkUnitPeriod::where('user_id', $user->id)
            ->where('work_unit_id', $unitB->id)
            ->where('status', GtkWorkUnitPeriod::STATUS_ACTIVE)
            ->count());
    }

    public function test_replacement_reuses_same_main_row_and_adds_new_period(): void
    {
        $user = $this->makeUser();
        $unitA = $this->makeUnit('A');
        $unitB = $this->makeUnit('B');
        $rowA = $this->placement($user, $unitA);
        $service = app(GtkPlacementLifecycleService::class);

        $firstA = $service->start($user, $unitA, null, '2026-01-01');
        $service->transfer($user, $unitA, $unitB, null, '2026-02-01');
        $secondA = $service->reactivate($rowA, null, '2026-03-01');

        $rowA->refresh();

        $this->assertSame($rowA->id, GtkWorkUnit::where('user_id', $user->id)
            ->where('work_unit_id', $unitA->id)
            ->value('id'));
        $this->assertSame(2, GtkWorkUnitPeriod::where('placement_id', $rowA->id)->count());
        $this->assertSame($firstA->id, GtkWorkUnitPeriod::where('placement_id', $rowA->id)
            ->where('status', GtkWorkUnitPeriod::STATUS_ENDED)
            ->value('id'));
        $this->assertSame($secondA->id, GtkWorkUnitPeriod::where('placement_id', $rowA->id)
            ->where('status', GtkWorkUnitPeriod::STATUS_ACTIVE)
            ->value('id'));
    }

    public function test_repeated_start_does_not_create_duplicate_row_or_active_period(): void
    {
        $user = $this->makeUser();
        $unit = $this->makeUnit();
        $service = app(GtkPlacementLifecycleService::class);

        $service->start($user, $unit, null, '2026-01-01');

        try {
            $service->start($user, $unit, null, '2026-01-02');
            $this->fail('Start kedua seharusnya ditolak karena periode aktif sudah ada.');
        } catch (PlacementLifecycleException) {
            // expected
        }

        $this->assertSame(1, GtkWorkUnit::where('user_id', $user->id)
            ->where('work_unit_id', $unit->id)
            ->count());
        $this->assertSame(1, GtkWorkUnitPeriod::where('user_id', $user->id)
            ->where('work_unit_id', $unit->id)
            ->where('status', GtkWorkUnitPeriod::STATUS_ACTIVE)
            ->count());
    }

    public function test_repeated_assignment_service_does_not_create_duplicate_placement(): void
    {
        config()->set('gtk_work_unit.lifecycle.enabled', true);

        $user = $this->makeUser();
        $unit = $this->makeUnit();
        $this->makeEmployment($user);
        $this->makePosition();

        $service = app(GtkPositionAssignmentService::class);
        $service->assign($user, $unit, 'Guru', 'guru_mapel');
        $service->assign($user, $unit, 'Guru', 'guru_mapel');

        $this->assertSame(1, GtkWorkUnit::where('user_id', $user->id)
            ->where('work_unit_id', $unit->id)
            ->count());
        $this->assertSame(1, GtkWorkUnitPeriod::where('user_id', $user->id)
            ->where('work_unit_id', $unit->id)
            ->where('status', GtkWorkUnitPeriod::STATUS_ACTIVE)
            ->count());
    }

    public function test_transfer_rolls_back_when_destination_already_has_active_period(): void
    {
        $user = $this->makeUser();
        $unitA = $this->makeUnit('A');
        $unitB = $this->makeUnit('B');
        $service = app(GtkPlacementLifecycleService::class);

        $service->start($user, $unitA, null, '2026-01-01');
        $service->start($user, $unitB, null, '2026-01-01', null, null, false);

        try {
            $service->transfer($user, $unitA, $unitB, null, '2026-02-01');
            $this->fail('Transfer seharusnya gagal karena unit tujuan sudah aktif.');
        } catch (PlacementLifecycleException) {
            // expected
        }

        $this->assertSame(1, GtkWorkUnitPeriod::where('user_id', $user->id)
            ->where('work_unit_id', $unitA->id)
            ->where('status', GtkWorkUnitPeriod::STATUS_ACTIVE)
            ->count());
    }

    public function test_legacy_placement_without_period_can_be_ended(): void
    {
        $user = $this->makeUser();
        $unit = $this->makeUnit();
        $row = $this->placement($user, $unit);

        $period = app(GtkPlacementLifecycleService::class)->end($row, today()->toDateString());

        $this->assertSame(GtkWorkUnitPeriod::STATUS_ENDED, $period->status);
        $this->assertSame($unit->id, $period->work_unit_id);
        $this->assertFalse($row->fresh()->is_primary);
    }

    public function test_position_assignment_service_flag_off_keeps_legacy_behavior(): void
    {
        config()->set('gtk_work_unit.lifecycle.enabled', false);

        $user = $this->makeUser();
        $unit = $this->makeUnit();
        $this->makeEmployment($user);
        $this->makePosition();

        app(GtkPositionAssignmentService::class)->assign($user, $unit, 'Guru', 'guru_mapel');

        $this->assertDatabaseHas('gtk_work_unit', [
            'user_id' => $user->id,
            'work_unit_id' => $unit->id,
            'is_primary' => true,
        ]);
        $this->assertSame(0, GtkWorkUnitPeriod::where('user_id', $user->id)->count());
    }

    public function test_flag_off_keeps_legacy_sync_behavior(): void
    {
        config()->set('gtk_work_unit.lifecycle.enabled', false);

        $user = $this->makeUser();
        $unitA = $this->makeUnit('A');
        $unitB = $this->makeUnit('B');
        $old = $this->placement($user, $unitA);

        $controller = app(GtkWizardController::class);
        $this->invokeSyncWorkUnit($controller, $user->id, $unitB->id, 'Guru');

        $old->refresh();

        $this->assertSame($unitB->id, $old->work_unit_id);
        $this->assertSame(0, GtkWorkUnitPeriod::where('user_id', $user->id)->count());
    }

    public function test_legacy_reader_query_still_works_without_period_rows(): void
    {
        $user = $this->makeUser();
        $unit = $this->makeUnit();
        $this->placement($user, $unit);

        $this->assertSame(1, GtkWorkUnit::where('user_id', $user->id)->count());
        $this->assertSame(0, GtkWorkUnitPeriod::where('user_id', $user->id)->count());
    }
}
