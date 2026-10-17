<?php

namespace Tests\Unit;

use App\Http\Controllers\GtkWizardController;
use Tests\TestCase;

class GtkImportNormalizationTest extends TestCase
{
    private GtkWizardController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = app(GtkWizardController::class);
    }

    /**
     * Test that scientific notation NUPY is converted to clean digit string.
     */
    public function test_nupy_scientific_notation_normalized(): void
    {
        $row = ['nupy' => '1.99108E+13', 'name' => 'Test', 'email' => 't@test.com'];
        $result = $this->callPrivateMethod('normalizeImportRow', $row);

        $this->assertEquals('19910800000000', $result['row']['nupy']);
    }

    /**
     * Test that numeric NIK is cleaned to digit string.
     */
    public function test_nik_numeric_cleaned(): void
    {
        $row = ['nik' => 5201020107910194.0, 'name' => 'Test', 'email' => 't@test.com'];
        $result = $this->callPrivateMethod('normalizeImportRow', $row);

        $this->assertEquals('5201020107910194', $result['row']['nik']);
    }

    /**
     * Test that lowercase jenis_kelamin is uppercased.
     */
    public function test_jenis_kelamin_lowercase_normalized(): void
    {
        $row = ['jenis_kelamin' => 'l', 'name' => 'Test', 'email' => 't@test.com'];
        $result = $this->callPrivateMethod('normalizeImportRow', $row);

        $this->assertEquals('L', $result['row']['jenis_kelamin']);
    }

    /**
     * Test that 'laki-laki' maps to 'L'.
     */
    public function test_jenis_kelamin_full_word_normalized(): void
    {
        $row = ['jenis_kelamin' => 'laki-laki', 'name' => 'Test', 'email' => 't@test.com'];
        $result = $this->callPrivateMethod('normalizeImportRow', $row);

        $this->assertEquals('L', $result['row']['jenis_kelamin']);
    }

    /**
     * Test that 'Menikah' maps to 'kawin' enum value.
     */
    public function test_status_perkawinan_menikah_normalized(): void
    {
        $row = ['status_perkawinan' => 'Menikah', 'name' => 'Test', 'email' => 't@test.com'];
        $result = $this->callPrivateMethod('normalizeImportRow', $row);

        $this->assertEquals('kawin', $result['row']['status_perkawinan']);
    }

    /**
     * Test that 'Belum Kawin' maps to 'belum_kawin' enum value.
     */
    public function test_status_perkawinan_belum_kawin_normalized(): void
    {
        $row = ['status_perkawinan' => 'Belum Kawin', 'name' => 'Test', 'email' => 't@test.com'];
        $result = $this->callPrivateMethod('normalizeImportRow', $row);

        $this->assertEquals('belum_kawin', $result['row']['status_perkawinan']);
    }

    /**
     * Test that 'Tetap' status_kepegawaian maps to 'PTY'.
     */
    public function test_status_kepegawaian_tetap_normalized_to_pty(): void
    {
        $row = ['status_kepegawaian' => 'Tetap', 'name' => 'Test', 'email' => 't@test.com'];
        $result = $this->callPrivateMethod('normalizeImportRow', $row);

        $this->assertEquals('PTY', $result['row']['status_kepegawaian']);
    }

    /**
     * Test that valid status_kepegawaian values are preserved.
     */
    public function test_status_kepegawaian_valid_values_preserved(): void
    {
        foreach (['PTT', 'PTY', 'GTT', 'GTY', 'KONTRAK', 'Percobaan', 'Magang'] as $status) {
            $row = ['status_kepegawaian' => $status, 'name' => 'Test', 'email' => 't@test.com'];
            $result = $this->callPrivateMethod('normalizeImportRow', $row);
            $this->assertEquals(strtoupper($status), $result['row']['status_kepegawaian'], "Failed for: $status");
        }
    }

    /**
     * Test that invalid status_kepegawaian is set to null.
     */
    public function test_invalid_status_kepegawaian_set_to_null(): void
    {
        $row = ['status_kepegawaian' => 'InvalidStatus', 'name' => 'Test', 'email' => 't@test.com'];
        $result = $this->callPrivateMethod('normalizeImportRow', $row);

        $this->assertNull($result['row']['status_kepegawaian']);
    }

    /**
     * Test that no_hp is cleaned to digits only.
     */
    public function test_no_hp_cleaned_to_digits(): void
    {
        $row = ['no_hp' => '+62 812-3456-7890', 'name' => 'Test', 'email' => 't@test.com'];
        $result = $this->callPrivateMethod('normalizeImportRow', $row);

        $this->assertEquals('6281234567890', $result['row']['no_hp']);
    }

    /**
     * Test that empty values remain untouched.
     */
    public function test_empty_values_untouched(): void
    {
        $row = [
            'name' => 'Test',
            'email' => 't@test.com',
            'nik' => null,
            'nupy' => null,
            'jenis_kelamin' => null,
            'status_perkawinan' => null,
            'status_kepegawaian' => null,
            'no_hp' => null,
        ];
        $result = $this->callPrivateMethod('normalizeImportRow', $row);

        $this->assertNull($result['row']['nik']);
        $this->assertNull($result['row']['nupy']);
        $this->assertNull($result['row']['jenis_kelamin']);
        $this->assertNull($result['row']['status_perkawinan']);
        $this->assertNull($result['row']['status_kepegawaian']);
        $this->assertNull($result['row']['no_hp']);
    }

    /**
     * Call a private method via reflection.
     */
    private function callPrivateMethod(string $method, array $row): array
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod($method);

        return $method->invoke($this->controller, $row);
    }
}
