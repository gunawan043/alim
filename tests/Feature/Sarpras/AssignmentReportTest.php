<?php

namespace Tests\Feature\Sarpras;

use App\Models\SarprasAssignmentReport;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\CreatesSarprasFixtures;

class AssignmentReportTest extends TestCase
{
    use CreatesSarprasFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function admin(): User
    {
        return User::factory()->create([
            'name' => 'Admin Sarpras',
            'is_system_admin' => true,
        ]);
    }

    private function completedOrder(array $overrides = []): WorkOrder
    {
        $asset = $this->createAsset();

        return WorkOrder::create(array_merge([
            'asset_id' => $asset->id,
            'assignee_id' => $this->fixtureUser()->id,
            'status' => 'completed',
            'scope_of_work' => 'Perbaikan AC ruang kelas',
            'actual_start' => now()->subHour(),
            'actual_end' => now(),
            'completion_notes' => 'AC menyala normal',
        ], $overrides));
    }

    /** @test */
    public function halaman_laporan_menampilkan_pekerjaan_yang_belum_dilaporkan(): void
    {
        $admin = $this->admin();
        $order = $this->completedOrder();

        $this->actingAs($admin)
            ->get("/{$admin->id}/sarpras/laporan-penugasan")
            ->assertOk()
            ->assertSee('Laporan Penugasan Sarpras')
            ->assertSee($order->order_number)
            ->assertSee('Isi Laporan');
    }

    /** @test */
    public function petugas_dapat_menyimpan_laporan_singkat(): void
    {
        $admin = $this->admin();
        $order = $this->completedOrder();

        $this->actingAs($admin)
            ->post("/{$admin->id}/sarpras/laporan-penugasan", [
                'work_order_id' => $order->id,
                'report_date' => today()->toDateString(),
                'work_done' => 'Mengganti kapasitor dan membersihkan filter AC.',
                'obstacles' => 'Tidak ada kendala',
                'follow_up' => 'Pantau satu minggu',
                'cost' => 150000,
                'condition_after' => 'baik',
                'duration_minutes' => 45,
                'action' => 'submit',
            ])
            ->assertRedirect(route('sarpras.assignment-report.index', ['tab' => 'laporan']));

        $this->assertDatabaseHas('sarpras_assignment_reports', [
            'work_order_id' => $order->id,
            'reported_by' => $admin->id,
            'status' => SarprasAssignmentReport::STATUS_SUBMITTED,
        ]);

        // Laporan muncul di tab Laporan...
        $this->actingAs($admin)
            ->get("/{$admin->id}/sarpras/laporan-penugasan?tab=laporan")
            ->assertOk()
            ->assertSee('Mengganti kapasitor');

        // ...dan work order tidak lagi menunggu laporan (flash sudah terpakai).
        $this->actingAs($admin)
            ->get("/{$admin->id}/sarpras/laporan-penugasan?tab=belum")
            ->assertOk()
            ->assertDontSee($order->order_number);
    }

    /** @test */
    public function satu_work_order_hanya_punya_satu_laporan(): void
    {
        $admin = $this->admin();
        $order = $this->completedOrder();

        $payload = [
            'work_order_id' => $order->id,
            'report_date' => today()->toDateString(),
            'work_done' => 'Perbaikan selesai.',
        ];

        $this->actingAs($admin)->post("/{$admin->id}/sarpras/laporan-penugasan", $payload)->assertRedirect();
        $this->actingAs($admin)->post("/{$admin->id}/sarpras/laporan-penugasan", $payload)->assertSessionHasErrors('work_order_id');

        $this->assertSame(1, SarprasAssignmentReport::where('work_order_id', $order->id)->count());
    }

    /** @test */
    public function laporan_wajib_menyebutkan_apa_yang_dikerjakan(): void
    {
        $admin = $this->admin();
        $order = $this->completedOrder();

        $this->actingAs($admin)
            ->post("/{$admin->id}/sarpras/laporan-penugasan", [
                'work_order_id' => $order->id,
                'report_date' => today()->toDateString(),
            ])
            ->assertSessionHasErrors('work_done');

        $this->assertDatabaseCount('sarpras_assignment_reports', 0);
    }

    /** @test */
    public function menyelesaikan_work_order_langsung_mengarah_ke_form_laporan(): void
    {
        $admin = $this->admin();
        $order = $this->completedOrder(['status' => 'in_progress', 'actual_end' => null]);

        $this->actingAs($admin)
            ->post("/{$admin->id}/sarpras/teknisi/{$order->id}/complete", [
                'notes' => 'Pekerjaan selesai, unit normal kembali.',
            ])
            ->assertRedirect(route('sarpras.assignment-report.index', ['work_order_id' => $order->id]));

        $this->assertSame('completed', $order->fresh()->status);
    }

    /** @test */
    public function koordinator_dapat_memverifikasi_laporan(): void
    {
        $admin = $this->admin();
        $order = $this->completedOrder();

        $report = SarprasAssignmentReport::create([
            'work_order_id' => $order->id,
            'asset_id' => $order->asset_id,
            'reported_by' => $admin->id,
            'report_date' => today(),
            'work_done' => 'Servis selesai.',
            'status' => SarprasAssignmentReport::STATUS_SUBMITTED,
        ]);

        $this->actingAs($admin)
            ->post("/{$admin->id}/sarpras/laporan-penugasan/{$report->id}/verify", [
                'verification_note' => 'Sesuai bukti foto.',
            ])
            ->assertRedirect();

        $report->refresh();
        $this->assertSame(SarprasAssignmentReport::STATUS_VERIFIED, $report->status);
        $this->assertSame($admin->id, $report->verified_by);

        $this->actingAs($admin)
            ->get("/{$admin->id}/sarpras/laporan-penugasan/{$report->id}")
            ->assertOk()
            ->assertSee('Servis selesai.');
    }
}
