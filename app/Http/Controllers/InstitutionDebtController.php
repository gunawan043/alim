<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\WorkUnit;
use App\Services\Pos\PosProcurementClient;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Analisis hutang lembaga. Data resmi tetap di BUMP; ALIM hanya menampilkan
 * ringkasan, tren, aging, dan rincian yang dikirim melalui API.
 */
class InstitutionDebtController extends Controller
{
    public function __construct(private readonly PosProcurementClient $client) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeAccess($request);

        $units = $this->accessibleUnits($request);
        $selectedUnitCode = trim((string) $request->input('unit', ''));
        $selectedUnit = $selectedUnitCode !== '' ? $units->firstWhere('code', $selectedUnitCode) : null;

        if ($selectedUnitCode !== '' && ! $selectedUnit) {
            abort(403, 'Satuan kerja tidak dapat diakses.');
        }

        $months = (int) $request->input('months', 6);
        if (! in_array($months, [3, 6, 12, 24], true)) {
            $months = 6;
        }

        $codes = $selectedUnit
            ? collect([$selectedUnit->code])
            : $units->pluck('code')->filter()->values();

        $debt = $this->emptyDebt($months);
        $error = null;

        if ($codes->isEmpty()) {
            $error = 'Satuan kerja belum terpetakan ke BUMP. Hubungi admin untuk memetakan satuan kerja.';
        } else {
            try {
                $result = $this->client->institutionDebtSummary([
                    'business_unit_codes' => $codes->implode(','),
                    'months' => $months,
                ]);
                $debt = array_replace_recursive($debt, (array) ($result['data'] ?? []));
            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
            }
        }

        return view('institution-debts.index', [
            'userId' => $userId,
            'units' => $units,
            'selectedUnit' => $selectedUnit,
            'months' => $months,
            'debt' => $debt,
            'error' => $error,
        ]);
    }

    private function authorizeAccess(Request $request): void
    {
        $user = $request->user();
        abort_unless(
            $user?->isSuperAdmin() || $user?->hasRole('Satuan Pendidikan'),
            403,
            'Halaman hutang lembaga hanya untuk Satuan Pendidikan.'
        );
    }

    /**
     * @return Collection<int, WorkUnit>
     */
    private function accessibleUnits(Request $request): Collection
    {
        $user = $request->user();

        if ($user?->isSuperAdmin()) {
            return WorkUnit::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
        }

        $schoolId = $request->attributes->get('schoolContextId');
        $school = $schoolId ? School::find($schoolId) : null;
        $primary = $user ? $user->gtkWorkUnits()->where('is_primary', true)->pluck('work_unit_id') : collect();
        $ids = collect([$school?->work_unit_id])->merge($primary)->filter()->unique();

        return WorkUnit::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    private function emptyDebt(int $months): array
    {
        $monthly = collect(range(0, $months - 1))
            ->map(fn (int $offset) => now()->startOfMonth()->subMonths($offset))
            ->reverse()
            ->map(fn ($date) => [
                'month' => $date->format('Y-m'),
                'label' => $date->locale('id')->translatedFormat('M Y'),
                'bump_new' => 0.0,
                'siplah_new' => 0.0,
                'bump_paid' => 0.0,
                'siplah_paid' => 0.0,
                'total_paid' => 0.0,
            ])
            ->values()
            ->all();

        return [
            'summary' => [
                'total_outstanding' => 0.0,
                'total_amount' => 0.0,
                'total_paid' => 0.0,
                'bump_outstanding' => 0.0,
                'siplah_outstanding' => 0.0,
                'open_items' => 0,
                'overdue_amount' => 0.0,
                'overdue_items' => 0,
                'due_soon_amount' => 0.0,
                'due_soon_items' => 0,
                'units_count' => 0,
                'average_outstanding' => 0.0,
                'collection_rate' => 0.0,
            ],
            'categories' => [
                ['key' => 'bump', 'label' => 'BUMP', 'amount' => 0.0, 'paid' => 0.0, 'outstanding' => 0.0, 'items' => 0],
                ['key' => 'siplah', 'label' => 'SIPLah', 'amount' => 0.0, 'paid' => 0.0, 'outstanding' => 0.0, 'items' => 0],
            ],
            'monthly' => $monthly,
            'aging' => [
                ['bucket' => 'current', 'label' => 'Belum jatuh tempo', 'bump' => 0.0, 'siplah' => 0.0, 'total' => 0.0],
                ['bucket' => '1-30', 'label' => '1–30 hari', 'bump' => 0.0, 'siplah' => 0.0, 'total' => 0.0],
                ['bucket' => '31-60', 'label' => '31–60 hari', 'bump' => 0.0, 'siplah' => 0.0, 'total' => 0.0],
                ['bucket' => '61-90', 'label' => '61–90 hari', 'bump' => 0.0, 'siplah' => 0.0, 'total' => 0.0],
                ['bucket' => 'over-90', 'label' => 'Lebih dari 90 hari', 'bump' => 0.0, 'siplah' => 0.0, 'total' => 0.0],
            ],
            'units' => [],
            'statuses' => [],
            'due_soon' => [],
            'items' => [],
            'generated_at' => null,
        ];
    }
}
