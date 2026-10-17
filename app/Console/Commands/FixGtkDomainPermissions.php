<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\StructuralAssignment;
use App\Models\StructuralPosition;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FixGtkDomainPermissions extends Command
{
    protected $signature = 'gtk:fix-domain-permissions';

    protected $description = 'Backfill StructuralAssignment for existing GTK so domain-based permissions work correctly.';

    public function handle(): int
    {
        $this->info('Starting GTK domain permission backfill...');

        $academicYearId = AcademicYear::where('is_active', true)
            ->orderBy('start_date', 'desc')
            ->value('id');

        if (! $academicYearId) {
            $this->error('No active academic year found.');

            return self::FAILURE;
        }

        // Get all GTK users with employment
        $gtks = User::whereHas('employment')->get(['id']);
        $total = $gtks->count();
        $this->info("Found {$total} GTK users.");

        $fixed = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($gtks as $gtk) {
            try {
                $employment = $gtk->employment;
                if (! $employment || ! $employment->jabatan_id) {
                    $skipped++;

                    continue;
                }

                // Find the primary work unit
                $primaryWU = $gtk->gtkWorkUnits->firstWhere('is_primary', true);
                if (! $primaryWU) {
                    $skipped++;

                    continue;
                }

                // Check if StructuralAssignment already exists
                $existing = StructuralAssignment::where('user_id', $gtk->id)
                    ->where('academic_year_id', $academicYearId)
                    ->first();

                if ($existing) {
                    $fixed++;

                    continue;
                }

                // Find the correct position ID for the work unit's domain
                // First try: find position that matches both the job title AND the work unit's domain
                $targetDomain = DB::table('work_units')
                    ->leftJoin('domains', 'work_units.domain_id', '=', 'domains.id')
                    ->where('work_units.id', $primaryWU->work_unit_id)
                    ->value('domains.id');

                if (! $targetDomain) {
                    // Fallback: use the position's own domain
                    $targetDomain = StructuralPosition::where('id', $employment->jabatan_id)
                        ->value('domain_id');
                }

                if (! $targetDomain) {
                    $skipped++;

                    continue;
                }

                // Find position: prefer matching name within target domain, fallback to any active position in domain
                $positionId = StructuralPosition::where('domain_id', $targetDomain)
                    ->whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$employment->jabatan ?? ''])
                    ->where('is_active', true)
                    ->value('id');

                if (! $positionId) {
                    $positionId = StructuralPosition::where('domain_id', $targetDomain)
                        ->where('is_active', true)
                        ->orderBy('urutan')
                        ->value('id');
                }

                if (! $positionId) {
                    $skipped++;

                    continue;
                }

                // Get school_id from work_unit
                $schoolId = DB::table('schools')
                    ->where('work_unit_id', $primaryWU->work_unit_id)
                    ->value('id');

                if (! $schoolId) {
                    $skipped++;

                    continue;
                }

                StructuralAssignment::create([
                    'id' => Str::uuid(),
                    'user_id' => $gtk->id,
                    'position_id' => $positionId,
                    'school_id' => $schoolId,
                    'academic_year_id' => $academicYearId,
                    'status' => 'active',
                    'start_date' => now()->toDateString(),
                    'notes' => 'Backfilled by gtk:fix-domain-permissions',
                ]);

                $fixed++;
            } catch (\Exception $e) {
                \Log::error('GTK domain fix failed for user '.$gtk->id, [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $errors++;
            }
        }

        $this->info("Fixed: {$fixed}, Skipped: {$skipped}, Errors: {$errors}");

        return self::SUCCESS;
    }
}
