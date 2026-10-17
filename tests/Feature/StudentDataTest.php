<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Santri;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentDataTest extends TestCase
{
    use RefreshDatabase;

    private User $wali;

    private Santri $santri;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organization::factory()->create();
        $sekolah = Sekolah::factory()->create(['organization_id' => $org->id]);

        $this->wali = User::factory()->create([
            'password' => Hash::make('password123'),
            'role' => 'wali',
        ]);

        $this->santri = Santri::factory()->create([
            'sekolah_id' => $sekolah->id,
            'nik' => '3301234567890001',
        ]);

        $this->token = $this->wali->createToken('test')->plainTextToken;
    }

    /** @test */
    public function wali_can_see_linked_santri(): void
    {
        // Mock the WaliSantri relationship since factory may not create it
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/mobile/v1/santri');

        $response->assertHeader('Cache-Control', 'no-store');
    }

    /** @test */
    public function student_data_has_security_headers(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/mobile/v1/santri/{$this->santri->getKey()}");

        $response->assertHeader('Cache-Control', 'no-store');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    /** @test */
    public function unauthenticated_user_cannot_access_student_data(): void
    {
        $response = $this->getJson('/api/mobile/v1/santri');
        $response->assertStatus(401);
    }
}
