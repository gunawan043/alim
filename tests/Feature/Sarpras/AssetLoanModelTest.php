<?php

namespace Tests\Feature\Sarpras;

use App\Models\AssetLoan;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssetLoanModelTest extends TestCase
{
    protected static $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! self::$migrated) {
            Artisan::call('migrate:fresh', ['--force' => true]);
            self::$migrated = true;
        }
    }

    /** @test */
    public function loan_uuid_is_generated_on_create()
    {
        $loan = AssetLoan::create([
            'loan_code' => 'LOAN-'.strtoupper(Str::random(6)),
            'loan_date' => now(),
            'expected_return_date' => now()->addDays(7),
            'status' => 'menunggu',
            'borrower_type' => 'internal',
            'borrower_name' => 'Test Borrower',
            'purpose' => 'Testing',
        ]);
        $this->assertNotNull($loan->id);
        $this->assertNotFalse(filter_var($loan->id, FILTER_VALIDATE_UUID));
    }

    /** @test */
    public function status_transitions()
    {
        $data = [
            'loan_code' => 'LOAN-'.strtoupper(Str::random(6)),
            'loan_date' => now(),
            'expected_return_date' => now()->addDays(7),
            'status' => 'menunggu',
            'borrower_type' => 'internal',
            'borrower_name' => 'Test Borrower',
            'purpose' => 'Testing',
        ];

        $loan = AssetLoan::create($data);
        $loan->update(['status' => 'disetujui']);
        $this->assertEquals('disetujui', $loan->status);

        $loan->update(['status' => 'aktif']);
        $this->assertEquals('aktif', $loan->status);

        $loan->update(['status' => 'selesai']);
        $this->assertEquals('selesai', $loan->status);
    }
}
