<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AdminPresensiHarian;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetRoom;
use App\Models\Lab\LabActivity;
use App\Models\Lab\LabActivityAttendance;
use App\Models\Lab\LabActivityItem;
use App\Models\Lab\LabCalibration;
use App\Models\Lab\LabConditionHistory;
use App\Models\Lab\LabDisposal;
use App\Models\Lab\LabEvaluation;
use App\Models\Lab\LabIncident;
use App\Models\Lab\LabInspection;
use App\Models\Lab\LabLoan;
use App\Models\Lab\LabProfile;
use App\Models\Lab\LabReport;
use App\Models\Lab\LabSafetyInspection;
use App\Models\Lab\LabSafetyInspectionItem;
use App\Models\Lab\LabScheduleSlot;
use App\Models\Lab\LabUsageRequest;
use App\Models\Lab\LabItemBatch;
use App\Models\Lab\LabItemProfile;
use App\Models\Lab\LabStockMovement;
use App\Models\School;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\User;
use App\Models\Uks\UksReferral;
use App\Services\Uks\UksClinicService;
use App\Services\Lab\LabAccess;
use App\Services\Pdf\DocumentPdfService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LabController extends Controller
{
    public function __construct(private readonly LabAccess $access) {}

    private function context(Request $request): array
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId') ?? $user?->employment?->school_id;

        if (! $schoolId && ! $this->access->isSystemAdmin($user)) {
            abort(403, 'Konteks satuan pendidikan belum tersedia.');
        }

        $yearId = $request->input('academic_year_id', AcademicYear::where('is_active', true)->value('id'));
        $year = $yearId ? AcademicYear::find($yearId) : null;
        $semester = (int) $request->input('semester', $year?->semester === 'genap' ? 2 : 1);
        if (! in_array($semester, [1, 2], true)) {
            $semester = 1;
        }

        $labs = LabProfile::with('assetRoom')
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->when(! $this->access->isSystemAdmin($user) && ! $this->access->isPrincipal($user), function ($q) use ($user) {
                $q->where(function ($inner) use ($user) {
                    $inner->where('coordinator_user_id', $user?->id)
                        ->orWhereNull('coordinator_user_id');
                });
            })
            ->orderBy('name')
            ->get();

        $lab = $labs->firstWhere('id', $request->input('lab_id')) ?: $labs->first();
        $rooms = AssetRoom::where('school_id', $schoolId)->where('is_active', true)
            ->where('room_type', 'laboratorium')->orderBy('room_name')->get();

        return compact('user', 'schoolId', 'yearId', 'year', 'semester', 'labs', 'lab', 'rooms');
    }

    private function requireLab(array $ctx): LabProfile
    {
        abort_unless($ctx['lab'], 404, 'Laboratorium belum dibuat. Buat profil laboratorium terlebih dahulu.');

        return $ctx['lab'];
    }

    private function ensureManage(LabProfile $lab): void
    {
        abort_unless($this->access->canManage(auth()->user(), $lab), 403, 'Anda tidak berwenang mengelola laboratorium ini.');
    }

    private function ensureApprove(LabProfile $lab): void
    {
        abort_unless($this->access->canApprove(auth()->user(), $lab), 403, 'Anda tidak berwenang menyetujui tindakan laboratorium ini.');
    }

    private function inventoryQuery(array $ctx, ?LabProfile $lab = null)
    {
        $lab ??= $ctx['lab'];

        return Asset::with(['category', 'room.building'])
            ->where('school_id', $ctx['schoolId'])
            ->where('is_active', true)
            ->whereNotIn('condition', ['hilang', 'dihapus'])
            ->where('status', '!=', 'dihapus')
            ->where(function ($q) use ($lab) {
                $q->whereHas('category', fn ($c) => $c->where('code', 'LAB-001')->orWhere('inventory_group', 'like', '%Laboratorium%'));
                if ($lab) {
                    $q->orWhereHas('labItemProfile', fn ($p) => $p->where('lab_id', $lab->id));
                }
            });
    }

    public function index(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $ctx['lab'];
        $inventory = $lab ? $this->inventoryQuery($ctx, $lab)->orderBy('asset_name')->get() : collect();
        $profiles = $lab ? LabItemProfile::where('lab_id', $lab->id)->get()->keyBy('asset_id') : collect();

        $stats = [
            'items' => $inventory->count(),
            'quantity' => (int) $inventory->sum('quantity'),
            'good' => $inventory->where('condition', 'baik')->count(),
            'damaged' => $inventory->whereIn('condition', ['rusak_ringan', 'rusak_sedang', 'rusak_berat'])->count(),
            'due_inspection' => $lab ? $lab->inspections()->whereIn('status', ['scheduled', 'overdue'])->count() : 0,
            'due_calibration' => $lab ? $lab->calibrations()->whereNotNull('next_calibration_date')->whereDate('next_calibration_date', '<', today())->count() : 0,
            'active_loans' => $lab ? $lab->loans()->whereIn('status', ['submitted', 'approved', 'handed_over', 'partially_returned', 'overdue'])->count() : 0,
            'upcoming_activities' => $lab ? $lab->activities()->whereDate('activity_date', '>=', today())->whereIn('status', ['draft', 'scheduled'])->count() : 0,
        ];

        $calibrations = $lab ? $lab->calibrations()->with('asset')->orderBy('next_calibration_date')->limit(10)->get() : collect();
        $loans = $lab ? $lab->loans()->with('items')->latest()->limit(10)->get() : collect();
        $assets = $inventory;

        return view('lab.index', array_merge($ctx, compact('lab', 'inventory', 'profiles', 'stats', 'calibrations', 'loans', 'assets')));
    }

    public function profileStore(Request $request)
    {
        $ctx = $this->context($request);
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:30',
            'asset_room_id' => 'nullable|uuid|exists:asset_rooms,id',
            'coordinator_user_id' => 'nullable|uuid|exists:users,id',
            'head_user_id' => 'nullable|uuid|exists:users,id',
            'notes' => 'nullable|string|max:2000',
        ]);

        $validated['school_id'] = $ctx['schoolId'];
        $validated['work_unit_id'] = School::whereKey($ctx['schoolId'])->value('work_unit_id');
        $validated['lab_type'] = 'ipa';
        $validated['is_active'] = true;
        if (empty($validated['coordinator_user_id'])) {
            $validated['coordinator_user_id'] = $request->user()->id;
        }
        if (empty($validated['head_user_id'])) {
            $validated['head_user_id'] = $request->user()->id;
        }

        if (! empty($validated['asset_room_id'])) {
            $room = AssetRoom::where('school_id', $ctx['schoolId'])->find($validated['asset_room_id']);
            if (! $room || $room->room_type !== 'laboratorium') {
                throw ValidationException::withMessages(['asset_room_id' => 'Ruang harus bertipe laboratorium pada satuan pendidikan ini.']);
            }
        }

        LabProfile::updateOrCreate(
            ['school_id' => $ctx['schoolId'], 'code' => $validated['code']],
            $validated
        );

        return back()->with('success', 'Profil laboratorium berhasil disimpan.');
    }

    public function inventoryStore(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $this->requireLab($ctx);
        $this->ensureManage($lab);

        $validated = $request->validate([
            'asset_id' => 'required|uuid|exists:assets,id',
            'item_kind' => 'required|in:alat,bahan,alat_ukur',
            'requires_calibration' => 'nullable|boolean',
            'calibration_interval_months' => 'nullable|integer|min:1|max:120',
            'min_stock' => 'nullable|integer|min:0',
            'storage_location' => 'nullable|string|max:150',
            'hazard_class' => 'nullable|string|max:100',
            'is_consumable' => 'nullable|boolean',
            'notes' => 'nullable|string|max:2000',
        ]);

        $asset = Asset::where('school_id', $ctx['schoolId'])->findOrFail($validated['asset_id']);
        LabItemProfile::updateOrCreate(
            ['asset_id' => $asset->id],
            [
                'lab_id' => $lab->id,
                'item_kind' => $validated['item_kind'],
                'requires_calibration' => (bool) ($validated['requires_calibration'] ?? false),
                'calibration_interval_months' => $validated['calibration_interval_months'] ?? 12,
                'min_stock' => $validated['min_stock'] ?? 0,
                'storage_location' => $validated['storage_location'] ?? null,
                'hazard_class' => $validated['hazard_class'] ?? null,
                'is_consumable' => (bool) ($validated['is_consumable'] ?? false),
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return back()->with('success', 'Item laboratorium berhasil disimpan.');
    }

    public function inventoryPrint(Request $request, string $userId)
    {
        $ctx = $this->context($request);
        $lab = $this->requireLab($ctx);
        $rows = $this->inventoryQuery($ctx, $lab)->orderBy('asset_name')->get();

        return app(DocumentPdfService::class)->fromView(
            'lab.print.inventory',
            ['rows' => $rows, 'lab' => $lab, 'school' => School::find($ctx['schoolId']), 'semester' => $ctx['semester'], 'year' => $ctx['year']],
            'Daftar-Inventaris-Laboratorium',
        );
    }

    public function batchIndex(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $ctx['lab'];
        $batches = $lab ? LabItemBatch::with('asset')->where('lab_id', $lab->id)->orderBy('expiry_date')->get() : collect();
        $movements = $lab ? LabStockMovement::with(['asset', 'batch'])->where('lab_id', $lab->id)->latest('occurred_at')->limit(50)->get() : collect();
        $assets = $lab ? $this->inventoryQuery($ctx, $lab)->orderBy('asset_name')->get() : collect();

        return view('lab.batches', array_merge($ctx, compact('lab', 'batches', 'movements', 'assets')));
    }

    public function batchStore(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $this->requireLab($ctx);
        $this->ensureManage($lab);
        $validated = $request->validate([
            'asset_id' => 'required|uuid|exists:assets,id',
            'batch_code' => 'nullable|string|max:80',
            'quantity' => 'required|integer|min:1',
            'unit' => 'nullable|string|max:30',
            'received_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:received_date',
            'notes' => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($validated, $ctx, $lab, $request) {
            $asset = Asset::where('school_id', $ctx['schoolId'])->lockForUpdate()->findOrFail($validated['asset_id']);
            if (! LabItemProfile::where('asset_id', $asset->id)->where('lab_id', $lab->id)->exists()) {
                throw ValidationException::withMessages(['asset_id' => 'Aset bukan inventaris laboratorium ini.']);
            }
            $batch = LabItemBatch::create([
                'lab_id' => $lab->id,
                'asset_id' => $asset->id,
                'batch_code' => $validated['batch_code'] ?? null,
                'quantity' => $validated['quantity'],
                'unit' => $validated['unit'] ?? $asset->unit ?: 'unit',
                'received_date' => $validated['received_date'] ?? null,
                'expiry_date' => $validated['expiry_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
            LabStockMovement::create([
                'lab_id' => $lab->id,
                'asset_id' => $asset->id,
                'batch_id' => $batch->id,
                'movement_type' => 'in',
                'quantity' => $validated['quantity'],
                'unit' => $batch->unit,
                'reference_type' => LabItemBatch::class,
                'reference_id' => $batch->id,
                'actor_id' => auth()->id(),
                'occurred_at' => now(),
            ]);
            $asset->increment('quantity', $validated['quantity']);
        });

        return back()->with('success', 'Batch/stok masuk berhasil dicatat.');
    }

    public function batchConsume(Request $request, string $userId, string $batch)
    {
        $ctx = $this->context($request);
        $lab = $this->requireLab($ctx);
        $this->ensureManage($lab);
        $record = LabItemBatch::where('lab_id', $lab->id)->findOrFail($batch);
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);
        if ($validated['quantity'] > $record->quantity) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah keluar melebihi stok batch.']);
        }

        DB::transaction(function () use ($record, $validated, $lab) {
            $record->decrement('quantity', $validated['quantity']);
            $record->asset?->decrement('quantity', $validated['quantity']);
            LabStockMovement::create([
                'lab_id' => $lab->id,
                'asset_id' => $record->asset_id,
                'batch_id' => $record->id,
                'movement_type' => 'out',
                'quantity' => -$validated['quantity'],
                'unit' => $record->unit,
                'reference_type' => LabItemBatch::class,
                'reference_id' => $record->id,
                'notes' => $validated['notes'] ?? null,
                'actor_id' => auth()->id(),
                'occurred_at' => now(),
            ]);
        });

        return back()->with('success', 'Pemakaian stok berhasil dicatat.');
    }

    public function inspectionIndex(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $ctx['lab'];
        $inspections = $lab ? $lab->inspections()->with('items')->latest('scheduled_date')->paginate(15)->withQueryString() : collect();
        $assets = $lab ? $this->inventoryQuery($ctx, $lab)->orderBy('asset_name')->get() : collect();

        return view('lab.inspection', array_merge($ctx, compact('lab', 'inspections', 'assets')));
    }

    public function inspectionStore(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $this->requireLab($ctx);
        $this->ensureManage($lab);

        $validated = $request->validate([
            'scheduled_date' => 'nullable|date',
            'performed_date' => 'nullable|date',
            'notes' => 'nullable|string|max:3000',
            'items' => 'required|array|min:1',
            'items.*.asset_id' => 'nullable|uuid|exists:assets,id',
            'items.*.item_name' => 'required|string|max:191',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.condition_code' => 'required|in:B,RR,RB',
            'items.*.maintenance_action' => 'nullable|string|max:191',
            'items.*.follow_up_type' => 'nullable|in:perbaikan,kalibrasi,penggantian',
            'items.*.follow_up' => 'nullable|string|max:2000',
            'items.*.inspected_at' => 'nullable|date',
        ]);

        $inspection = DB::transaction(function () use ($validated, $ctx, $lab) {
            $inspection = LabInspection::create([
                'lab_id' => $lab->id,
                'school_id' => $ctx['schoolId'],
                'academic_year_id' => $ctx['yearId'],
                'semester' => $ctx['semester'],
                'scheduled_date' => $validated['scheduled_date'] ?? today(),
                'performed_date' => $validated['performed_date'] ?? today(),
                'inspector_id' => auth()->id(),
                'status' => 'completed',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $index => $row) {
                $asset = ! empty($row['asset_id']) ? Asset::where('school_id', $ctx['schoolId'])->find($row['asset_id']) : null;
                $inspection->items()->create([
                    'asset_id' => $asset?->id,
                    'item_name' => $row['item_name'],
                    'quantity' => $row['quantity'],
                    'condition_code' => $row['condition_code'],
                    'maintenance_action' => $row['maintenance_action'] ?? null,
                    'follow_up_type' => $row['follow_up_type'] ?? null,
                    'follow_up' => $row['follow_up'] ?? null,
                    'inspected_at' => $row['inspected_at'] ?? $inspection->performed_date,
                    'sort' => $index,
                ]);

                if ($asset) {
                    $previous = $this->conditionCode($asset->condition);
                    $asset->update(['condition' => $this->assetCondition($row['condition_code'])]);
                    LabConditionHistory::create([
                        'lab_id' => $lab->id,
                        'asset_id' => $asset->id,
                        'condition_code' => $row['condition_code'],
                        'previous_code' => $previous,
                        'source_type' => LabInspection::class,
                        'source_id' => $inspection->id,
                        'notes' => $row['follow_up'] ?? null,
                        'actor_id' => auth()->id(),
                        'occurred_at' => now(),
                    ]);
                }
            }

            return $inspection;
        });

        $nextDate = $inspection->performed_date?->copy()->addMonths(3);
        if ($nextDate) {
            LabInspection::firstOrCreate(
                [
                    'lab_id' => $lab->id,
                    'scheduled_date' => $nextDate->toDateString(),
                    'status' => 'scheduled',
                ],
                [
                    'school_id' => $ctx['schoolId'],
                    'academic_year_id' => $ctx['yearId'],
                    'semester' => $ctx['semester'],
                    'inspector_id' => auth()->id(),
                    'notes' => 'Jadwal pemeriksaan berkala otomatis tiga bulan setelah pemeriksaan terakhir.',
                ]
            );
        }

        return redirect()->route('lab.inspection.index', ['userId' => $request->route('userId'), 'lab_id' => $lab->id])
            ->with('success', 'Pemeriksaan berkala berhasil disimpan.');
    }

    public function inspectionPrint(Request $request, string $userId, string $inspection)
    {
        $ctx = $this->context($request);
        $record = LabInspection::with(['lab', 'items.asset'])->where('lab_id', $ctx['lab']?->id)->findOrFail($inspection);

        return app(DocumentPdfService::class)->fromView(
            'lab.print.inspection',
            ['inspection' => $record, 'school' => $ctx['schoolId'] ? School::find($ctx['schoolId']) : null],
            'Pemeriksaan-Laboratorium-'.$record->id,
            'landscape',
        );
    }

    public function calibrationIndex(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $ctx['lab'];
        $calibrations = $lab ? $lab->calibrations()->with('asset')->latest('next_calibration_date')->paginate(15)->withQueryString() : collect();
        $assets = $lab ? $this->inventoryQuery($ctx, $lab)->orderBy('asset_name')->get() : collect();

        return view('lab.calibration', array_merge($ctx, compact('lab', 'calibrations', 'assets')));
    }

    public function calibrationStore(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $this->requireLab($ctx);
        $this->ensureManage($lab);

        $validated = $request->validate([
            'asset_id' => 'required|uuid|exists:assets,id',
            'last_calibration_date' => 'nullable|date',
            'next_calibration_date' => 'nullable|date|after_or_equal:last_calibration_date',
            'calibration_type' => 'required|in:I,E',
            'performed_by' => 'nullable|uuid|exists:users,id',
            'institution_name' => 'nullable|string|max:191',
            'result' => 'nullable|string',
            'notes' => 'nullable|string',
            'certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $asset = Asset::where('school_id', $ctx['schoolId'])->findOrFail($validated['asset_id']);
        $profile = LabItemProfile::where('asset_id', $asset->id)->where('lab_id', $lab->id)->first();
        if (! $profile) {
            throw ValidationException::withMessages(['asset_id' => 'Alat belum terdaftar pada laboratorium ini.']);
        }
        $interval = $profile?->calibration_interval_months ?: 12;
        $next = ! empty($validated['next_calibration_date'])
            ? Carbon::parse($validated['next_calibration_date'])
            : (! empty($validated['last_calibration_date']) ? Carbon::parse($validated['last_calibration_date'])->addMonths($interval) : null);

        LabCalibration::create([
            'lab_id' => $lab->id,
            'asset_id' => $asset->id,
            'last_calibration_date' => $validated['last_calibration_date'] ?? null,
            'next_calibration_date' => $next,
            'calibration_type' => $validated['calibration_type'],
            'status' => $next && $next->lt(today()) ? 'OD' : 'OS',
            'performed_by' => $validated['performed_by'] ?? null,
            'institution_name' => $validated['institution_name'] ?? null,
            'result' => $validated['result'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'certificate_path' => $request->file('certificate')?->store('lab/calibrations', 'local'),
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Data kalibrasi berhasil disimpan.');
    }

    public function calibrationPrint(Request $request, string $userId)
    {
        $ctx = $this->context($request);
        $lab = $this->requireLab($ctx);
        $rows = $lab->calibrations()->with('asset')->orderBy('next_calibration_date')->get();

        return app(DocumentPdfService::class)->fromView(
            'lab.print.calibration',
            ['rows' => $rows, 'lab' => $lab, 'school' => School::find($ctx['schoolId']), 'semester' => $ctx['semester'], 'year' => $ctx['year']],
            'Jadwal-Kalibrasi-Laboratorium',
            'landscape',
        );
    }

    public function calibrationDownload(Request $request, string $userId, string $calibration)
    {
        $ctx = $this->context($request);
        $record = LabCalibration::where('lab_id', $ctx['lab']?->id)->findOrFail($calibration);
        abort_unless($record->certificate_path && Storage::disk('local')->exists($record->certificate_path), 404);

        return Storage::disk('local')->download($record->certificate_path, 'sertifikat-kalibrasi-'.$record->id.'.'.pathinfo($record->certificate_path, PATHINFO_EXTENSION));
    }

    public function loanIndex(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $ctx['lab'];
        $loans = $lab ? $lab->loans()->with(['items.asset', 'borrower', 'approver'])->latest('loan_date')->paginate(15)->withQueryString() : collect();
        $assets = $lab
            ? Asset::with('labItemProfile')->whereHas('labItemProfile')->where('is_active', true)->where('is_bookable', true)->whereNotIn('condition', ['rusak_berat', 'hilang', 'dihapus'])->orderBy('asset_name')->get()
            : collect();

        return view('lab.loan', array_merge($ctx, compact('lab', 'loans', 'assets')));
    }

    public function loanStore(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $this->requireLab($ctx);

        $validated = $request->validate([
            'borrower_name' => 'required|string|max:191',
            'institution' => 'nullable|string|max:191',
            'loan_date' => 'required|date',
            'purpose' => 'required|string|max:3000',
            'planned_return_date' => 'nullable|date|after_or_equal:loan_date',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.asset_id' => 'required|uuid|exists:assets,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        $assetItems = collect($validated['items'])->map(function (array $row) {
            $asset = Asset::findOrFail($row['asset_id']);
            if (! LabItemProfile::where('asset_id', $asset->id)->exists()) {
                throw ValidationException::withMessages(['items' => $asset->asset_name.' bukan inventaris laboratorium.']);
            }
            if (in_array($asset->condition, ['rusak_berat', 'hilang', 'dihapus'], true) || $asset->status === 'dihapus') {
                throw ValidationException::withMessages(['items' => $asset->asset_name.' tidak layak dipinjam.']);
            }
            if ((int) $row['quantity'] > (int) $asset->quantity) {
                throw ValidationException::withMessages(['items' => $asset->asset_name.' melebihi jumlah stok.']);
            }

            return ['asset' => $asset, 'row' => $row];
        });

        $crossSchool = $assetItems->contains(fn (array $item) => (string) $item['asset']->school_id !== (string) $ctx['schoolId']);

        DB::transaction(function () use ($validated, $ctx, $lab, $assetItems, $crossSchool) {
            $loan = LabLoan::create([
                'lab_id' => $lab->id,
                'school_id' => $ctx['schoolId'],
                'academic_year_id' => $ctx['yearId'],
                'semester' => $ctx['semester'],
                'borrower_user_id' => auth()->id(),
                'borrower_name' => $validated['borrower_name'],
                'institution' => $validated['institution'] ?? null,
                'loan_date' => $validated['loan_date'],
                'purpose' => $validated['purpose'],
                'planned_return_date' => $validated['planned_return_date'] ?? null,
                'status' => $crossSchool ? 'submitted' : 'approved',
                'approved_by' => $crossSchool ? null : auth()->id(),
                'approved_at' => $crossSchool ? null : now(),
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($assetItems as $item) {
                $asset = $item['asset'];
                $loan->items()->create([
                    'asset_id' => $asset->id,
                    'asset_school_id' => $asset->school_id,
                    'origin_room_id' => $asset->room_id,
                    'loan_location' => $validated['institution'] ?? $lab->name,
                    'item_name' => $asset->asset_name,
                    'quantity' => $item['row']['quantity'],
                    'unit' => $asset->unit ?: 'unit',
                    'condition_on_loan' => $this->conditionCode($asset->condition),
                    'notes' => $item['row']['notes'] ?? null,
                ]);
            }
        });

        return back()->with('success', $crossSchool
            ? 'Peminjaman lintas sekolah diajukan dan menunggu persetujuan pimpinan.'
            : 'Peminjaman berhasil dicatat.');
    }

    public function loanApprove(Request $request, string $userId, string $loan)
    {
        $ctx = $this->context($request);
        $lab = $this->requireLab($ctx);
        $record = LabLoan::where('lab_id', $lab->id)->findOrFail($loan);

        if (! $this->access->canManage($request->user(), $lab) && ! $this->access->canApprove($request->user(), $lab)) {
            abort(403, 'Anda tidak berwenang menyetujui peminjaman ini.');
        }
        abort_unless($record->status === 'submitted', 422, 'Peminjaman tidak dalam status menunggu persetujuan.');

        $record->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);

        return back()->with('success', 'Peminjaman disetujui.');
    }

    public function loanHandover(Request $request, string $userId, string $loan)
    {
        $ctx = $this->context($request);
        $record = LabLoan::where('lab_id', $ctx['lab']?->id)->findOrFail($loan);
        $this->ensureManage($ctx['lab']);
        abort_unless($record->status === 'approved', 422, 'Peminjaman belum disetujui.');
        $record->update(['status' => 'handed_over', 'handed_over_by' => auth()->id()]);
        foreach ($record->items as $item) {
            if ($item->asset) {
                $item->update(['loan_location' => $record->institution ?: $record->lab?->name]);
                $item->asset->update(['status' => 'dipinjam']);
            }
        }

        return back()->with('success', 'Alat diserahkan kepada peminjam.');
    }

    public function loanReturn(Request $request, string $userId, string $loan)
    {
        $ctx = $this->context($request);
        $lab = $this->requireLab($ctx);
        $this->ensureManage($lab);
        $record = LabLoan::where('lab_id', $lab->id)->with('items.asset')->findOrFail($loan);

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|uuid',
            'items.*.quantity_returned' => 'required|integer|min:0',
            'items.*.condition_on_return' => 'required|in:baik,rusak_ringan,rusak_sedang,rusak_berat,hilang',
            'items.*.damage_notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($validated, $record, $lab, $ctx) {
            foreach ($validated['items'] as $row) {
                $item = $record->items->firstWhere('id', $row['id']);
                if (! $item) {
                    continue;
                }
                $item->update([
                    'quantity_returned' => min($row['quantity_returned'], $item->quantity),
                    'condition_on_return' => $row['condition_on_return'],
                    'return_location' => $lab->name,
                    'damage_notes' => $row['damage_notes'] ?? null,
                ]);
                if ($item->asset) {
                    if ($item->origin_room_id) {
                        $item->asset->update(['room_id' => $item->origin_room_id]);
                    }
                    $previous = $this->conditionCode($item->asset->condition);
                    $item->asset->update([
                        'condition' => $row['condition_on_return'],
                        'status' => 'tersedia',
                    ]);
                    LabConditionHistory::create([
                        'lab_id' => $lab->id,
                        'asset_id' => $item->asset->id,
                        'condition_code' => $this->conditionCode($row['condition_on_return']),
                        'previous_code' => $previous,
                        'source_type' => LabLoan::class,
                        'source_id' => $record->id,
                        'notes' => $row['damage_notes'] ?? null,
                        'actor_id' => auth()->id(),
                        'occurred_at' => now(),
                    ]);
                }
            }

            $allReturned = $record->items()->get()->every(fn ($i) => $i->quantity_returned >= $i->quantity);
            $record->update([
                'status' => $allReturned ? 'returned' : 'partially_returned',
                'actual_return_date' => today(),
                'received_by' => auth()->id(),
            ]);
        });

        return back()->with('success', 'Pengembalian berhasil dicatat.');
    }

    public function loanPrint(Request $request, string $userId, string $loan)
    {
        $ctx = $this->context($request);
        $record = LabLoan::with(['lab', 'items.asset', 'borrower'])->where('lab_id', $ctx['lab']?->id)->findOrFail($loan);

        return app(DocumentPdfService::class)->fromView(
            'lab.print.loan',
            ['loan' => $record, 'school' => School::find($ctx['schoolId'])],
            'Peminjaman-Alat-Laboratorium',
        );
    }

    public function activityIndex(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $ctx['lab'];
        $activities = $lab ? $lab->activities()->with(['teacher', 'subject', 'studyGroup', 'report'])->latest('activity_date')->paginate(15)->withQueryString() : collect();
        $subjects = $lab ? Subject::where('school_id', $ctx['schoolId'])->orderBy('name')->get() : collect();
        $groups = $lab ? StudyGroup::where('school_id', $ctx['schoolId'])->where('is_active', true)->orderBy('name')->get() : collect();
        $assets = $lab ? $this->inventoryQuery($ctx, $lab)->orderBy('asset_name')->get() : collect();

        return view('lab.activity', array_merge($ctx, compact('lab', 'activities', 'subjects', 'groups', 'assets')));
    }

    public function activityStore(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $this->requireLab($ctx);

        $validated = $request->validate([
            'title' => 'required|string|max:191',
            'activity_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after_or_equal:start_time',
            'subject_id' => 'nullable|uuid|exists:subjects,id',
            'study_group_id' => 'nullable|uuid|exists:study_groups,id',
            'participants_count' => 'nullable|integer|min:0',
            'risk_notes' => 'nullable|string|max:3000',
            'notes' => 'nullable|string|max:3000',
            'items' => 'nullable|array',
            'items.*.asset_id' => 'nullable|uuid|exists:assets,id',
            'items.*.item_name' => 'required_with:items|string|max:191',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.unit' => 'nullable|string|max:30',
        ]);

        $activity = DB::transaction(function () use ($validated, $ctx, $lab) {
            $activity = LabActivity::create([
                'lab_id' => $lab->id,
                'school_id' => $ctx['schoolId'],
                'academic_year_id' => $ctx['yearId'],
                'semester' => $ctx['semester'],
                'teacher_id' => auth()->id(),
                'subject_id' => $validated['subject_id'] ?? null,
                'study_group_id' => $validated['study_group_id'] ?? null,
                'title' => $validated['title'],
                'activity_date' => $validated['activity_date'],
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'participants_count' => $validated['participants_count'] ?? 0,
                'status' => 'scheduled',
                'risk_notes' => $validated['risk_notes'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] ?? [] as $row) {
                $activity->items()->create([
                    'asset_id' => $row['asset_id'] ?? null,
                    'item_name' => $row['item_name'],
                    'quantity' => $row['quantity'] ?? 1,
                    'unit' => $row['unit'] ?? 'unit',
                    'readiness_status' => 'belum',
                ]);
            }

            return $activity;
        });

        return redirect()->route('lab.activity.show', ['userId' => $request->route('userId'), 'activity' => $activity->id])
            ->with('success', 'Kegiatan laboratorium berhasil dibuat.');
    }

    public function activityShow(Request $request, string $userId, string $activity)
    {
        $ctx = $this->context($request);
        $record = LabActivity::with(['lab', 'teacher', 'subject', 'studyGroup', 'items.asset', 'report', 'attendances.student'])
            ->where('lab_id', $ctx['lab']?->id)->findOrFail($activity);

        $attendance = collect();
        if ($record->study_group_id) {
            $attendance = AdminPresensiHarian::with('student')
                ->where('study_group_id', $record->study_group_id)
                ->where('academic_year_id', $ctx['yearId'])
                ->where('semester', $ctx['semester'] === 2 ? 'genap' : 'ganjil')
                ->whereDate('attendance_date', $record->activity_date)
                ->get();
        }

        return view('lab.activity-show', array_merge($ctx, ['activity' => $record, 'classAttendance' => $attendance]));
    }

    public function reportStore(Request $request, string $userId, string $activity)
    {
        $ctx = $this->context($request);
        $record = LabActivity::where('lab_id', $ctx['lab']?->id)->findOrFail($activity);
        $this->ensureManage($ctx['lab']);

        $validated = $request->validate([
            'report_date' => 'required|date',
            'time_start' => 'nullable|date_format:H:i',
            'time_end' => 'nullable|date_format:H:i',
            'title' => 'required|string|max:191',
            'subject_name' => 'nullable|string|max:191',
            'class_name' => 'nullable|string|max:100',
            'participants_total' => 'required|integer|min:0',
            'participants_present' => 'required|integer|min:0',
            'participants_absent' => 'required|integer|min:0',
            'absent_names' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validated['participants_present'] + $validated['participants_absent'] !== $validated['participants_total']) {
            throw ValidationException::withMessages(['participants_total' => 'Jumlah hadir + absen harus sama dengan total peserta.']);
        }

        LabReport::updateOrCreate(
            ['activity_id' => $record->id],
            [
                'report_date' => $validated['report_date'],
                'time_start' => $validated['time_start'] ?? null,
                'time_end' => $validated['time_end'] ?? null,
                'title' => $validated['title'],
                'subject_name' => $validated['subject_name'] ?? null,
                'class_name' => $validated['class_name'] ?? null,
                'participants_total' => $validated['participants_total'],
                'participants_present' => $validated['participants_present'],
                'participants_absent' => $validated['participants_absent'],
                'absent_names' => collect(preg_split('/\r\n|\r|\n/', (string) ($validated['absent_names'] ?? '')))->filter()->values()->all(),
                'notes' => $validated['notes'] ?? null,
                'teacher_id' => auth()->id(),
                'status' => 'submitted',
                'created_by' => auth()->id(),
            ]
        );

        $record->update(['status' => 'completed']);

        return back()->with('success', 'Berita acara praktikum berhasil disimpan.');
    }

    public function reportPrint(Request $request, string $userId, string $activity)
    {
        $ctx = $this->context($request);
        $record = LabReport::with(['activity.lab', 'activity.studyGroup', 'activity.subject', 'teacher'])
            ->whereHas('activity', fn ($q) => $q->where('lab_id', $ctx['lab']?->id))
            ->where('activity_id', $activity)->firstOrFail();

        return app(DocumentPdfService::class)->fromView(
            'lab.print.report',
            ['report' => $record, 'school' => School::find($ctx['schoolId'])],
            'Berita-Acara-Praktikum',
        );
    }

    public function requestIndex(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId') ?? $user?->employment?->school_id;
        abort_unless($schoolId, 403, 'Konteks satuan pendidikan belum tersedia.');
        $lab = LabProfile::where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->first();
        $yearId = AcademicYear::where('is_active', true)->value('id');
        $year = $yearId ? AcademicYear::find($yearId) : null;
        $semester = $year?->semester === 'genap' ? 2 : 1;
        $subjects = Subject::where('school_id', $schoolId)->orderBy('name')->get();
        $myRequests = LabUsageRequest::with('lab')->where('applicant_id', $user->id)->latest('usage_date')->limit(10)->get();

        return view('lab.request', compact('user', 'schoolId', 'lab', 'yearId', 'year', 'semester', 'subjects', 'myRequests'));
    }

    public function requestStore(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId') ?? $user?->employment?->school_id;
        abort_unless($schoolId, 403, 'Konteks satuan pendidikan belum tersedia.');
        $lab = LabProfile::where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->firstOrFail();
        $yearId = AcademicYear::where('is_active', true)->value('id');
        $year = $yearId ? AcademicYear::find($yearId) : null;
        $semester = $year?->semester === 'genap' ? 2 : 1;
        $validated = $request->validate([
            'subject_id' => 'nullable|uuid|exists:subjects,id',
            'usage_date' => 'required|date|after_or_equal:today',
            'activity_type' => 'required|in:praktikum,penelitian,lainnya',
            'other_activity' => 'nullable|string|max:191',
            'participants_count' => 'nullable|integer|min:0',
            'risk_notes' => 'nullable|string|max:3000',
            'applicant_position_class' => 'nullable|string|max:191',
        ]);

        LabUsageRequest::create(array_merge($validated, [
            'lab_id' => $lab->id,
            'school_id' => $schoolId,
            'academic_year_id' => $yearId,
            'semester' => $semester,
            'applicant_id' => $user->id,
            'status' => 'submitted',
        ]));

        return back()->with('success', 'Pengajuan izin laboratorium dikirim ke koordinator.');
    }

    public function usageIndex(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $ctx['lab'];
        $requests = $lab ? $lab->usageRequests()->with(['applicant', 'subject', 'activity'])->latest('usage_date')->paginate(15)->withQueryString() : collect();
        $subjects = $lab ? Subject::where('school_id', $ctx['schoolId'])->orderBy('name')->get() : collect();

        return view('lab.usage', array_merge($ctx, compact('lab', 'requests', 'subjects')));
    }

    public function usageStore(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $this->requireLab($ctx);

        $validated = $request->validate([
            'applicant_position_class' => 'nullable|string|max:191',
            'subject_id' => 'nullable|uuid|exists:subjects,id',
            'usage_date' => 'required|date|after_or_equal:today',
            'activity_type' => 'required|in:praktikum,penelitian,lainnya',
            'other_activity' => 'nullable|string|max:191',
            'participants_count' => 'nullable|integer|min:0',
            'risk_notes' => 'nullable|string|max:3000',
        ]);

        LabUsageRequest::create(array_merge($validated, [
            'lab_id' => $lab->id,
            'school_id' => $ctx['schoolId'],
            'academic_year_id' => $ctx['yearId'],
            'semester' => $ctx['semester'],
            'applicant_id' => auth()->id(),
            'status' => 'submitted',
        ]));

        return back()->with('success', 'Izin penggunaan laboratorium diajukan.');
    }

    public function usageApprove(Request $request, string $userId, string $usage)
    {
        $ctx = $this->context($request);
        $lab = $this->requireLab($ctx);
        $this->ensureManage($lab);
        $record = LabUsageRequest::where('lab_id', $lab->id)->findOrFail($usage);
        $record->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);

        return back()->with('success', 'Izin penggunaan disetujui.');
    }

    public function usageReject(Request $request, string $userId, string $usage)
    {
        $ctx = $this->context($request);
        $lab = $this->requireLab($ctx);
        $this->ensureManage($lab);
        $record = LabUsageRequest::where('lab_id', $lab->id)->findOrFail($usage);
        $validated = $request->validate(['rejection_reason' => 'required|string|max:1000']);
        $record->update(['status' => 'rejected', 'rejection_reason' => $validated['rejection_reason'], 'approved_by' => auth()->id(), 'approved_at' => now()]);

        return back()->with('success', 'Izin penggunaan ditolak.');
    }

    public function scheduleIndex(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $ctx['lab'];
        $slots = $lab ? $lab->scheduleSlots()->with(['studyGroup', 'subject', 'teacher'])->where('academic_year_id', $ctx['yearId'])->where('semester', $ctx['semester'])->orderBy('day_of_week')->orderBy('slot_index')->get() : collect();
        $groups = $lab ? StudyGroup::where('school_id', $ctx['schoolId'])->where('is_active', true)->orderBy('name')->get() : collect();
        $subjects = $lab ? Subject::where('school_id', $ctx['schoolId'])->orderBy('name')->get() : collect();

        return view('lab.schedule', array_merge($ctx, compact('lab', 'slots', 'groups', 'subjects')));
    }

    public function scheduleStore(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $this->requireLab($ctx);
        $this->ensureManage($lab);

        $validated = $request->validate([
            'day_of_week' => 'required|integer|between:1,6',
            'slot_index' => 'required|integer|min:1|max:20',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after_or_equal:start_time',
            'study_group_id' => 'nullable|uuid|exists:study_groups,id',
            'subject_id' => 'nullable|uuid|exists:subjects,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        LabScheduleSlot::updateOrCreate(
            [
                'lab_id' => $lab->id,
                'academic_year_id' => $ctx['yearId'],
                'semester' => $ctx['semester'],
                'day_of_week' => $validated['day_of_week'],
                'slot_index' => $validated['slot_index'],
            ],
            [
                'school_id' => $ctx['schoolId'],
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'study_group_id' => $validated['study_group_id'] ?? null,
                'subject_id' => $validated['subject_id'] ?? null,
                'teacher_id' => auth()->id(),
                'source' => 'manual',
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return back()->with('success', 'Jadwal laboratorium disimpan.');
    }

    public function schedulePrint(Request $request, string $userId)
    {
        $ctx = $this->context($request);
        $lab = $this->requireLab($ctx);
        $slots = $lab->scheduleSlots()->with(['studyGroup', 'subject', 'teacher'])->where('academic_year_id', $ctx['yearId'])->where('semester', $ctx['semester'])->orderBy('day_of_week')->orderBy('slot_index')->get();

        return app(DocumentPdfService::class)->fromView(
            'lab.print.schedule',
            ['lab' => $lab, 'slots' => $slots, 'school' => School::find($ctx['schoolId']), 'semester' => $ctx['semester'], 'year' => $ctx['year']],
            'Jadwal-Penggunaan-Laboratorium',
            'landscape',
        );
    }

    public function safetyIndex(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $ctx['lab'];
        $inspections = $lab ? $lab->safetyInspections()->with('items')->latest('inspection_date')->paginate(15)->withQueryString() : collect();

        return view('lab.safety', array_merge($ctx, compact('lab', 'inspections')));
    }

    public function safetyStore(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $this->requireLab($ctx);
        $this->ensureManage($lab);

        $validated = $request->validate([
            'inspection_date' => 'required|date',
            'inspector_position' => 'nullable|string|max:191',
            'items' => 'required|array|size:8',
            'items.*.source_no' => 'required|integer|min:1|max:8',
            'items.*.aspect' => 'required|string|max:191',
            'items.*.status' => 'required|in:Baik,Tidak Baik',
            'items.*.notes' => 'nullable|string|max:500',
            'findings' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'follow_up_date' => 'nullable|date',
            'follow_up_action' => 'nullable|string',
            'follow_up_person' => 'nullable|string|max:191',
        ]);

        DB::transaction(function () use ($validated, $ctx, $lab) {
            $inspection = LabSafetyInspection::create([
                'lab_id' => $lab->id,
                'school_id' => $ctx['schoolId'],
                'academic_year_id' => $ctx['yearId'],
                'semester' => $ctx['semester'],
                'inspection_date' => $validated['inspection_date'],
                'inspector_id' => auth()->id(),
                'inspector_position' => $validated['inspector_position'] ?? null,
                'status' => collect($validated['items'])->contains(fn ($i) => $i['status'] === 'Tidak Baik') ? 'findings_open' : 'closed',
                'findings' => $validated['findings'] ?? null,
                'recommendations' => $validated['recommendations'] ?? null,
                'follow_up_date' => $validated['follow_up_date'] ?? null,
                'follow_up_action' => $validated['follow_up_action'] ?? null,
                'follow_up_person' => $validated['follow_up_person'] ?? null,
            ]);

            foreach ($validated['items'] as $index => $row) {
                $inspection->items()->create([
                    'source_no' => $row['source_no'],
                    'aspect' => $row['aspect'],
                    'status' => $row['status'],
                    'notes' => $row['notes'] ?? null,
                    'sort' => $index,
                ]);
            }
        });

        return back()->with('success', 'Inspeksi keselamatan berhasil disimpan.');
    }

    public function safetyPrint(Request $request, string $userId, string $inspection)
    {
        $ctx = $this->context($request);
        $record = LabSafetyInspection::with(['lab', 'items'])->where('lab_id', $ctx['lab']?->id)->findOrFail($inspection);

        return app(DocumentPdfService::class)->fromView(
            'lab.print.safety',
            ['inspection' => $record, 'school' => School::find($ctx['schoolId'])],
            'Inspeksi-Keselamatan-Laboratorium',
        );
    }

    public function incidentIndex(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $ctx['lab'];
        $incidents = $lab ? $lab->incidents()->latest('incident_date')->paginate(15)->withQueryString() : collect();
        $referrals = $lab ? UksReferral::where('school_id', $ctx['schoolId'])->latest('referred_at')->limit(50)->get() : collect();
        $students = $lab ? \App\Models\Student::where('school_id', $ctx['schoolId'])->where('status', 'active')->orderBy('name')->get(['id', 'name', 'nis']) : collect();

        return view('lab.incident', array_merge($ctx, compact('lab', 'incidents', 'referrals', 'students')));
    }

    public function incidentStore(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $this->requireLab($ctx);

        $validated = $request->validate([
            'incident_date' => 'required|date',
            'incident_time' => 'nullable|date_format:H:i',
            'reporter_position' => 'nullable|string|max:191',
            'incident_type' => 'required|in:cedera,kerusakan_alat,tumpahan_bahan_kimia,lainnya',
            'other_type' => 'nullable|string|max:191',
            'chronology' => 'required|string',
            'location' => 'nullable|string|max:191',
            'victim_name' => 'nullable|string|max:191',
            'victim_student_id' => 'nullable|uuid|exists:students,id',
            'injury_type' => 'nullable|string|max:191',
            'first_aid' => 'nullable|string',
            'equipment_involved' => 'nullable|string',
            'immediate_action' => 'nullable|string',
            'prevention' => 'nullable|string',
            'personnel' => 'nullable|string',
            'follow_up' => 'nullable|string',
            'uks_referral_id' => 'nullable|uuid|exists:uks_referrals,id',
            'create_uks_referral' => 'nullable|boolean',
            'facility_name' => 'nullable|required_if:create_uks_referral,1|string|max:191',
            'facility_type' => 'nullable|string|max:30',
            'urgency' => 'nullable|in:rutin,segera,darurat',
            'referral_reason' => 'nullable|required_if:create_uks_referral,1|string|max:2000',
        ]);

        DB::transaction(function () use ($validated, $ctx, $lab, $request) {
            $incidentData = $validated;
            unset($incidentData['create_uks_referral'], $incidentData['facility_name'], $incidentData['facility_type'], $incidentData['urgency'], $incidentData['referral_reason']);
            $incident = LabIncident::create(array_merge($incidentData, [
                'lab_id' => $lab->id,
                'school_id' => $ctx['schoolId'],
                'reporter_id' => auth()->id(),
                'status' => 'reported',
                'is_confidential' => true,
            ]));

            if ($request->boolean('create_uks_referral') && ! empty($validated['victim_student_id'])) {
                $student = \App\Models\Student::findOrFail($validated['victim_student_id']);
                $clinic = app(UksClinicService::class);
                $patient = $clinic->register($student, [
                    'school_id' => $ctx['schoolId'],
                    'academic_year_id' => $ctx['yearId'],
                    'patient_type' => 'rawat',
                    'chief_complaint' => $validated['chronology'] ?? null,
                    'diagnosis' => $validated['injury_type'] ?? null,
                    'treatment' => $validated['first_aid'] ?? null,
                    'notes' => 'Dibuat otomatis dari laporan insiden laboratorium.',
                ], $ctx['schoolId'], auth()->id());

                $referral = $clinic->createReferral($patient, [
                    'facility_name' => $validated['facility_name'],
                    'facility_type' => $validated['facility_type'] ?? 'klinik',
                    'referred_at' => $incident->incident_date->setTimeFromTimeString($incident->incident_time ?: now()->format('H:i:s')),
                    'urgency' => $validated['urgency'] ?? 'rutin',
                    'diagnosis' => $validated['injury_type'] ?? null,
                    'reason' => $validated['referral_reason'],
                ], auth()->id());

                $incident->update(['uks_referral_id' => $referral->id]);
            }
        });

        return back()->with('success', 'Laporan insiden berhasil disimpan.');
    }

    public function incidentShow(Request $request, string $userId, string $incident)
    {
        $ctx = $this->context($request);
        $record = LabIncident::where('lab_id', $ctx['lab']?->id)->findOrFail($incident);

        return view('lab.incident-show', array_merge($ctx, ['incident' => $record]));
    }

    public function incidentPrint(Request $request, string $userId, string $incident)
    {
        $ctx = $this->context($request);
        $record = LabIncident::where('lab_id', $ctx['lab']?->id)->findOrFail($incident);

        return app(DocumentPdfService::class)->fromView(
            'lab.print.incident',
            ['incident' => $record, 'school' => School::find($ctx['schoolId'])],
            'Laporan-Insiden-Laboratorium',
        );
    }

    public function disposalIndex(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $ctx['lab'];
        $disposals = $lab ? $lab->disposals()->with('items.asset')->latest()->paginate(15)->withQueryString() : collect();
        $assets = $lab ? $this->inventoryQuery($ctx, $lab)->orderBy('asset_name')->get() : collect();

        return view('lab.disposal', array_merge($ctx, compact('lab', 'disposals', 'assets')));
    }

    public function disposalStore(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $this->requireLab($ctx);
        $this->ensureManage($lab);

        $validated = $request->validate([
            'disposal_date' => 'nullable|date',
            'follow_up' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.asset_id' => 'nullable|uuid|exists:assets,id',
            'items.*.item_name' => 'required|string|max:191',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'nullable|string|max:30',
            'items.*.condition_code' => 'nullable|in:B,RR,RB',
            'items.*.reason' => 'required|string|max:2000',
            'items.*.method' => 'required|string|max:100',
            'items.*.notes' => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($validated, $ctx, $lab) {
            $disposal = LabDisposal::create([
                'lab_id' => $lab->id,
                'school_id' => $ctx['schoolId'],
                'academic_year_id' => $ctx['yearId'],
                'semester' => $ctx['semester'],
                'disposal_number' => 'LAB-DIS-'.now()->format('Ymd-His'),
                'requested_by' => auth()->id(),
                'disposal_date' => $validated['disposal_date'] ?? null,
                'follow_up' => $validated['follow_up'] ?? null,
                'status' => 'submitted',
            ]);

            foreach ($validated['items'] as $index => $row) {
                $disposal->items()->create([
                    'asset_id' => $row['asset_id'] ?? null,
                    'item_name' => $row['item_name'],
                    'quantity' => $row['quantity'],
                    'unit' => $row['unit'] ?? 'unit',
                    'condition_code' => $row['condition_code'] ?? null,
                    'reason' => $row['reason'],
                    'method' => $row['method'],
                    'notes' => $row['notes'] ?? null,
                    'sort' => $index,
                ]);
            }
        });

        return back()->with('success', 'Form penghapusan diajukan.');
    }

    public function disposalApprove(Request $request, string $userId, string $disposal)
    {
        $ctx = $this->context($request);
        $lab = $this->requireLab($ctx);
        $this->ensureApprove($lab);
        $record = LabDisposal::with('items.asset')->where('lab_id', $lab->id)->findOrFail($disposal);

        DB::transaction(function () use ($record) {
            foreach ($record->items as $item) {
                if ($item->asset) {
                    $item->asset->update([
                        'condition' => 'dihapus',
                        'status' => 'dihapus',
                        'is_active' => false,
                        'disposal_method' => $item->method,
                        'disposal_date' => $record->disposal_date ?: today(),
                        'disposal_reason' => $item->reason,
                    ]);
                }
            }
            $record->update(['status' => 'executed', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        });

        return back()->with('success', 'Penghapusan disetujui dan aset ditandai dihapus tanpa kehilangan riwayat.');
    }

    public function disposalReject(Request $request, string $userId, string $disposal)
    {
        $ctx = $this->context($request);
        $lab = $this->requireLab($ctx);
        $this->ensureApprove($lab);
        $record = LabDisposal::where('lab_id', $lab->id)->findOrFail($disposal);
        $validated = $request->validate(['rejection_reason' => 'required|string|max:1000']);
        $record->update(['status' => 'rejected', 'rejection_reason' => $validated['rejection_reason'], 'approved_by' => auth()->id(), 'approved_at' => now()]);

        return back()->with('success', 'Penghapusan ditolak.');
    }

    public function disposalPrint(Request $request, string $userId, string $disposal)
    {
        $ctx = $this->context($request);
        $record = LabDisposal::with(['items.asset', 'lab'])->where('lab_id', $ctx['lab']?->id)->findOrFail($disposal);

        return app(DocumentPdfService::class)->fromView(
            'lab.print.disposal',
            ['disposal' => $record, 'school' => School::find($ctx['schoolId'])],
            'Penghapusan-Inventaris-Laboratorium',
            'landscape',
        );
    }

    public function evaluationIndex(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $ctx['lab'];
        $evaluations = $lab ? $lab->evaluations()->latest('evaluation_date')->paginate(15)->withQueryString() : collect();

        return view('lab.evaluation', array_merge($ctx, compact('lab', 'evaluations')));
    }

    public function evaluationStore(Request $request)
    {
        $ctx = $this->context($request);
        $lab = $this->requireLab($ctx);
        $this->ensureManage($lab);

        $validated = $request->validate([
            'evaluation_date' => 'required|date',
            'findings' => 'required|string',
            'recommendations' => 'nullable|string',
            'follow_up' => 'nullable|string',
        ]);

        LabEvaluation::create(array_merge($validated, [
            'lab_id' => $lab->id,
            'school_id' => $ctx['schoolId'],
            'academic_year_id' => $ctx['yearId'],
            'semester' => $ctx['semester'],
            'evaluated_by' => auth()->id(),
            'status' => 'finalized',
        ]));

        return back()->with('success', 'Evaluasi laboratorium disimpan.');
    }

    private function conditionCode(?string $condition): ?string
    {
        return match ($condition) {
            'baik' => 'B',
            'rusak_ringan', 'rusak_sedang' => 'RR',
            'rusak_berat', 'hilang', 'dihapus' => 'RB',
            default => null,
        };
    }

    private function assetCondition(string $code): string
    {
        return match ($code) {
            'B' => 'baik',
            'RR' => 'rusak_ringan',
            'RB' => 'rusak_berat',
            default => 'baik',
        };
    }
}
