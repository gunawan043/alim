<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\ElectricityMeter;
use App\Models\ElectricityOutage;
use App\Models\ElectricityOutageAction;
use App\Models\ElectricityTokenPurchase;
use App\Models\GtkWorkUnit;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\Sarpras\PhotoProofService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SarprasElectricityController extends SarprasBaseController
{
    public function __construct(protected PhotoProofService $photos) {}

    public function index(Request $request)
    {
        $this->authorizeElectricity();

        $period = $request->input('period', now()->format('Y-m'));
        $periodStart = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $periodEnd = (clone $periodStart)->endOfMonth();

        $meters = ElectricityMeter::with(['purchases' => fn ($q) => $q->whereBetween('period', [$periodStart, $periodEnd])])
            ->orderBy('name')->get();

        $purchases = ElectricityTokenPurchase::with(['meter', 'recorder'])
            ->whereBetween('period', [$periodStart, $periodEnd])
            ->orderByDesc('purchased_at')
            ->get();

        $outages = ElectricityOutage::with(['reporter', 'workUnit', 'actions.officer'])
            ->orderByDesc('occurred_at')
            ->limit(30)
            ->get();

        $stats = [
            'meters' => $meters->count(),
            'purchased' => $purchases->count(),
            'amount' => $purchases->sum('amount'),
            'open_outages' => $outages->where('status', 'open')->count(),
        ];

        $users = User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('sarpras.electricity.index', compact('meters', 'purchases', 'outages', 'stats', 'period', 'users'));
    }

    public function storeMeter(Request $request)
    {
        $this->authorizeElectricity();

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'meter_type' => 'required|in:prabayar,pascabayar',
            'meter_number' => 'required|string|max:80|unique:electricity_meters,meter_number',
            'location' => 'nullable|string|max:191',
            'default_amount' => 'nullable|numeric|min:0',
            'payment_due_day' => 'nullable|integer|min:1|max:31',
            'notes' => 'nullable|string|max:1000',
        ]);

        ElectricityMeter::create([...$validated, 'is_active' => true, 'created_by' => auth()->id()]);

        return back()->with('success', 'Meter listrik ditambahkan.');
    }

    public function toggleMeter(string $meterId)
    {
        $this->authorizeElectricity();
        $meter = ElectricityMeter::findOrFail($meterId);
        $meter->update(['is_active' => ! $meter->is_active]);

        return back()->with('success', 'Status meter diperbarui.');
    }

    public function storePurchase(Request $request, string $meterId)
    {
        $this->authorizeElectricity();
        $meter = ElectricityMeter::findOrFail($meterId);

        $validated = $request->validate([
            'period' => 'required|date_format:Y-m',
            'amount' => 'required|numeric|min:1',
            'purchased_at' => 'nullable|date',
            'receipt_url' => 'nullable|url|max:2000',
            'receipt' => 'nullable|image|max:8192',
            'notes' => 'nullable|string|max:1000',
        ]);

        $period = Carbon::createFromFormat('Y-m', $validated['period'])->startOfMonth();
        $receipt = $this->photos->store($request->file('receipt'), $validated['receipt_url'] ?? null, 'electricity/receipts');

        ElectricityTokenPurchase::updateOrCreate(
            ['meter_id' => $meter->id, 'period' => $period->toDateString()],
            [
                'amount' => $validated['amount'],
                'purchased_at' => $validated['purchased_at'] ?? today(),
                'receipt_path' => $receipt['path'],
                'receipt_url' => $receipt['url'],
                'notes' => $validated['notes'] ?? null,
                'recorded_by' => auth()->id(),
            ]
        );

        return back()->with('success', 'Pengisian token/tagihan listrik dicatat.');
    }

    public function storeOutage(Request $request)
    {
        $this->authorizeElectricity();

        $validated = $request->validate([
            'occurred_at' => 'required|date',
            'location' => 'required|string|max:191',
            'reporter_name' => 'nullable|string|max:191',
            'outage_type' => 'required|in:'.implode(',', ElectricityOutage::TYPE_OPTIONS),
            'cause' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:2000',
            'before_url' => 'nullable|url|max:2000',
            'before_photo' => 'nullable|image|max:8192',
        ]);

        $photo = $this->photos->store($request->file('before_photo'), $validated['before_url'] ?? null, 'electricity/outages');

        ElectricityOutage::create([
            'occurred_at' => $validated['occurred_at'],
            'location' => $validated['location'],
            'reporter_id' => auth()->id(),
            'reporter_name' => $validated['reporter_name'] ?? auth()->user()?->name,
            'outage_type' => $validated['outage_type'],
            'cause' => $validated['cause'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'before_photo_path' => $photo['path'],
            'before_photo_url' => $photo['url'],
            'status' => 'open',
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Kejadian listrik padam dicatat.');
    }

    public function showOutage(string $outageId)
    {
        $this->authorizeElectricity();

        $outage = ElectricityOutage::with(['actions.officer', 'reporter', 'workUnit'])->findOrFail($outageId);
        $users = User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('sarpras.electricity.outage', compact('outage', 'users'));
    }

    public function storeAction(Request $request, string $outageId)
    {
        $this->authorizeElectricity();
        $outage = ElectricityOutage::findOrFail($outageId);

        $validated = $request->validate([
            'action' => 'required|string|max:1000',
            'officer_id' => 'nullable|uuid|exists:users,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $outage->actions()->create([...$validated, 'officer_id' => $validated['officer_id'] ?? auth()->id()]);

        return back()->with('success', 'Tindakan penanganan ditambahkan.');
    }

    public function resolveOutage(Request $request, string $outageId)
    {
        $this->authorizeElectricity();
        $outage = ElectricityOutage::findOrFail($outageId);

        $validated = $request->validate([
            'restored_at' => 'required|date',
            'notes' => 'nullable|string|max:2000',
            'after_url' => 'nullable|url|max:2000',
            'after_photo' => 'nullable|image|max:8192',
        ]);

        $photo = $this->photos->store($request->file('after_photo'), $validated['after_url'] ?? null, 'electricity/outages');

        $outage->update([
            'restored_at' => $validated['restored_at'],
            'status' => 'resolved',
            'notes' => $validated['notes'] ?? $outage->notes,
            'after_photo_path' => $photo['path'] ?? $outage->after_photo_path,
            'after_photo_url' => $photo['url'] ?? $outage->after_photo_url,
        ]);

        return back()->with('success', 'Listrik dinyatakan menyala kembali.');
    }

    private function authorizeElectricity(): void
    {
        if ($this->isUrtLeadership()) {
            return;
        }

        $user = auth()->user();
        $jabatan = $user
            ? Str::upper(GtkWorkUnit::query()->where('user_id', $user->id)->pluck('jabatan')->implode(' '))
            : '';

        abort_unless(
            str_contains($jabatan, 'LISTRIK') || str_contains($jabatan, 'TEKNISI'),
            403,
            'Hanya URT/TU atau petugas listrik yang dapat mengelola data listrik.'
        );
    }
}
