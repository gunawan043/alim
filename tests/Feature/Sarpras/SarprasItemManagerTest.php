<?php

namespace Tests\Feature\Sarpras;

use App\Models\ChecklistTemplate;
use App\Models\ChecklistTemplateItem;
use App\Models\CleaningArea;
use App\Models\CleaningTask;
use App\Models\PertamananAgenda;
use App\Models\PertamananAgendaItem;
use App\Models\WasteSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesSarprasFixtures;

class SarprasItemManagerTest extends TestCase
{
    use CreatesSarprasFixtures;
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'name' => 'Admin Pengaturan URT',
            'is_system_admin' => true,
        ]);
    }

    private function url(string $path = 'pengaturan-item'): string
    {
        return "/{$this->admin->id}/sarpras/{$path}";
    }

    /** @test */
    public function admin_can_open_item_manager_tabs(): void
    {
        foreach (['area', 'agenda', 'program', 'sampah', 'checklist'] as $tab) {
            $this->actingAs($this->admin)
                ->get($this->url('pengaturan-item?tab='.$tab))
                ->assertOk();
        }
    }

    /** @test */
    public function admin_can_manage_waste_source(): void
    {
        $source = WasteSource::create([
            'name' => 'Dapur Lama',
            'source_type' => 'dapur',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post($this->url("pengaturan-item/sumber-sampah/{$source->id}"), [
                'name' => 'Dapur Utama',
                'source_type' => 'kantin',
                'notes' => 'Dekat area makan',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('waste_sources', [
            'id' => $source->id,
            'name' => 'Dapur Utama',
            'source_type' => 'kantin',
        ]);

        $this->actingAs($this->admin)
            ->post($this->url("pengaturan-item/sumber-sampah/{$source->id}/toggle"))
            ->assertRedirect();

        $this->assertDatabaseHas('waste_sources', [
            'id' => $source->id,
            'is_active' => false,
        ]);

        $this->actingAs($this->admin)
            ->delete($this->url("pengaturan-item/sumber-sampah/{$source->id}"))
            ->assertRedirect();

        $this->assertDatabaseMissing('waste_sources', ['id' => $source->id]);
    }

    /** @test */
    public function admin_can_add_update_and_delete_checklist_item(): void
    {
        $template = ChecklistTemplate::create([
            'code' => 'TEST-'.Str::upper(Str::random(6)),
            'name' => 'Checklist Test',
            'workflow_type' => 'maintenance',
            'category_slug' => 'gedung',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post($this->url("pengaturan-item/checklist-template/{$template->id}/items"), [
                'group_label' => 'Lantai',
                'label' => 'Lantai bersih',
                'standard' => 'Tidak ada noda',
                'method' => 'Inspeksi visual',
                'frequency' => 'harian',
                'response_type' => 'boolean',
                'is_required' => true,
            ])
            ->assertRedirect();

        $item = ChecklistTemplateItem::where('template_id', $template->id)->firstOrFail();
        $this->assertSame('Lantai bersih', $item->label);
        $this->assertSame(1, $item->sequence);

        $this->actingAs($this->admin)
            ->put($this->url("pengaturan-item/checklist-items/{$item->id}"), [
                'group_label' => 'Dinding',
                'label' => 'Dinding bersih',
                'frequency' => 'mingguan',
                'response_type' => 'boolean',
                'is_required' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('checklist_template_items', [
            'id' => $item->id,
            'label' => 'Dinding bersih',
            'group_label' => 'Dinding',
        ]);

        $this->actingAs($this->admin)
            ->delete($this->url("pengaturan-item/checklist-items/{$item->id}"))
            ->assertRedirect();

        $this->assertDatabaseMissing('checklist_template_items', ['id' => $item->id]);
    }

    /** @test */
    public function admin_can_regroup_cleaning_and_agenda_items(): void
    {
        $area = CleaningArea::create([
            'name' => 'Gedung Test',
            'area_type' => 'kantor',
            'created_by' => $this->admin->id,
        ]);
        $task = CleaningTask::create([
            'area_id' => $area->id,
            'title' => 'Mengepel',
            'group_label' => 'Lama',
            'created_by' => $this->admin->id,
        ]);

        $agenda = PertamananAgenda::create([
            'agenda_date' => now()->toDateString(),
            'academic_year' => now()->year.'/'.(now()->year + 1),
            'created_by' => $this->admin->id,
        ]);
        $agendaItem = PertamananAgendaItem::create([
            'agenda_id' => $agenda->id,
            'section' => 'pemeliharaan',
            'title' => 'Menyiram tanaman',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post($this->url('pengaturan-item/kebersihan-tasks/regroup'), [
                'task_ids' => [$task->id],
                'group_label' => 'Lantai',
            ])
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post($this->url('pengaturan-item/agenda-items/regroup'), [
                'item_ids' => [$agendaItem->id],
                'section' => 'pembibitan',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cleaning_tasks', ['id' => $task->id, 'group_label' => 'Lantai']);
        $this->assertDatabaseHas('pertamanan_agenda_items', ['id' => $agendaItem->id, 'section' => 'pembibitan']);
    }

    /** @test */
    public function admin_can_add_item_agenda_for_a_date(): void
    {
        $this->actingAs($this->admin)
            ->post($this->url('pengaturan-item/agenda-items'), [
                'agenda_date' => now()->toDateString(),
                'title' => 'Menyiram tanaman taman',
                'section' => 'pembibitan',
                'group_title' => 'Pemeliharaan Rutin',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $agenda = PertamananAgenda::whereDate('agenda_date', now()->toDateString())->firstOrFail();
        $this->assertDatabaseHas('pertamanan_agenda_items', [
            'agenda_id' => $agenda->id,
            'title' => 'Menyiram tanaman taman',
            'section' => 'pembibitan',
            'group_title' => 'Pemeliharaan Rutin',
            'status' => 'belum',
        ]);

        $item = $agenda->items()->firstOrFail();
        $this->actingAs($this->admin)
            ->put($this->url("pengaturan-item/agenda-items/{$item->id}"), [
                'title' => 'Menyiram tanaman taman (revisi)',
                'section' => 'permintaan_darurat',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('pertamanan_agenda_items', [
            'id' => $item->id,
            'title' => 'Menyiram tanaman taman (revisi)',
            'section' => 'permintaan_darurat',
        ]);
    }

    /** @test */
    public function admin_can_manage_waste_facility_from_item_manager(): void
    {
        $this->actingAs($this->admin)
            ->post($this->url('sampah/fasilitas'), [
                'name' => 'TPS Test',
                'facility_type' => 'tps',
                'condition' => 'baik',
                'location' => 'Sisi timur',
                'capacity_kg' => 500,
            ])
            ->assertRedirect();

        $facility = \App\Models\WasteFacility::where('name', 'TPS Test')->firstOrFail();

        $this->actingAs($this->admin)
            ->put($this->url("sampah/fasilitas/{$facility->id}"), [
                'name' => 'TPS Test Diperbarui',
                'facility_type' => 'tps',
                'condition' => 'rusak_ringan',
                'capacity_kg' => 750,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('waste_facilities', [
            'id' => $facility->id,
            'name' => 'TPS Test Diperbarui',
            'capacity_kg' => 750,
        ]);

        $this->actingAs($this->admin)
            ->post($this->url("sampah/fasilitas/{$facility->id}/toggle"))
            ->assertRedirect();
        $this->assertDatabaseHas('waste_facilities', ['id' => $facility->id, 'is_active' => false]);
    }
}
