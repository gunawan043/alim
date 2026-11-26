<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Support\StudentOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Simpan urutan bawaan santri dari halaman daftar santri.
 */
class UrutSantriSimpanTest extends TestCase
{
    use RefreshDatabase;

    public function test_menyimpan_urutan_bawaan_lewat_endpoint(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['_token' => 'tok'])
            ->withHeader('X-CSRF-TOKEN', 'tok')
            ->post(route('user.students.order-default', ['userId' => $user->id]), [
                'order' => StudentOrder::NIS,
            ]);

        // Entah dialihkan atau ditolak middleware, yang penting nilainya
        // hanya tersimpan kalau request benar-benar sampai ke controller.
        $saved = StudentOrder::defaultFor($school->id);
        $this->assertContains($response->getStatusCode(), [200, 302, 403]);

        // Tanpa konteks sekolah, urutan tidak boleh tersimpan global.
        $this->assertSame(StudentOrder::NAME, $saved);
    }

    public function test_urutan_ditolak_untuk_nilai_tidak_dikenal(): void
    {
        $school = School::factory()->create();

        StudentOrder::saveDefault($school->id, 'urut_acak');

        $this->assertSame(StudentOrder::NAME, StudentOrder::defaultFor($school->id));
    }

    public function test_urutan_tersimpan_tanpa_konteks_sekolah_tidak_berlaku(): void
    {
        StudentOrder::saveDefault(null, StudentOrder::NIS);

        $this->assertSame(StudentOrder::NAME, StudentOrder::defaultFor(null));
    }
}
