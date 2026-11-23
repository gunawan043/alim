<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * `alim:verify-connect-schema` memeriksa objek database nyata, bukan catatan
 * migrasi. Test ini menjaga agar perintah tidak berhenti mendeteksi skema
 * yang hilang setelah migrasi "Ran" tetapi tabelnya belum ada.
 */
class VerifyConnectSchemaCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_skema_lengkap_lulus(): void
    {
        $this->artisan('alim:verify-connect-schema')
            ->assertExitCode(0);
    }

    public function test_tabel_hilang_terdeteksi(): void
    {
        Schema::drop('connect_sso_handoffs');

        $this->artisan('alim:verify-connect-schema')
            ->expectsOutputToContain('tabel connect_sso_handoffs')
            ->assertExitCode(1);
    }

    public function test_kolom_hilang_terdeteksi(): void
    {
        Schema::table('approval_commands', function ($table) {
            $table->dropColumn('idempotency_key');
        });

        $this->artisan('alim:verify-connect-schema')
            ->expectsOutputToContain('idempotency_key')
            ->assertExitCode(1);
    }
}
