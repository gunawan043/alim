<?php

namespace Tests\Feature\Sarpras;

use App\Models\AssetLoan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesSarprasFixtures;

class AssetLoanModelTest extends TestCase
{
    use CreatesSarprasFixtures;
    use RefreshDatabase;

    /** @test */
    public function loan_uuid_is_generated_on_create()
    {
        $loan = $this->makeLoan();

        $this->assertNotNull($loan->id);
        $this->assertTrue(Str::isUuid((string) $loan->id));
    }

    /** @test */
    public function status_transitions()
    {
        $loan = $this->makeLoan();

        $this->assertEquals('pending', $loan->status);

        $loan->update(['status' => 'approved']);
        $this->assertEquals('approved', $loan->fresh()->status);

        $loan->update(['status' => 'dipinjam']);
        $this->assertEquals('dipinjam', $loan->fresh()->status);

        $loan->update(['status' => 'dikembalikan']);
        $this->assertEquals('dikembalikan', $loan->fresh()->status);
    }

    /** @test */
    public function status_options_cover_whole_lifecycle()
    {
        $this->assertEquals([
            'pending', 'approved', 'dipinjam', 'dikembalikan',
            'terlambat', 'hilang', 'dibatalkan',
        ], AssetLoan::STATUS_OPTIONS);
    }

    protected function makeLoan(array $overrides = []): AssetLoan
    {
        $asset = $this->createAsset();
        $borrower = $this->createUser();

        return AssetLoan::create(array_merge([
            'asset_id' => $asset->id,
            'work_unit_id' => $asset->work_unit_id,
            'school_id' => $asset->school_id,
            'borrower_id' => $borrower->id,
            'purpose' => 'Testing',
            'loan_date' => now()->toDateString(),
            'expected_return_date' => now()->addDays(7)->toDateString(),
            'condition_on_loan' => 'baik',
            'status' => 'pending',
        ], $overrides));
    }
}
