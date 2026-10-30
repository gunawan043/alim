<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\WorkUnit;
use App\Services\Pos\PosProcurementClient;
use App\Support\UserJabatan;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * UI pengajuan SIPLah. Tidak ada tabel SIPLah di ALIM; semua data proposal
 * disimpan dan diproses oleh BUMP melalui API.
 */
class SiplahController extends Controller
{
    public const CATEGORIES = [
        'ATK', 'Peralatan Kantor', 'Buku & Referensi', 'Elektronik',
        'Mebel', 'Kebersihan', 'Konsumsi', 'Lainnya',
    ];

    public function __construct(private readonly PosProcurementClient $client) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeAccess($request);
        $unitIds = $this->accessibleUnitIds($request);

        try {
            $result = $this->client->siplahProposals(array_filter([
                'status' => $request->input('status'),
                'work_unit_id' => $request->input('work_unit_id'),
                'search' => $request->input('search'),
                'per_page' => 100,
            ]));
        } catch (RuntimeException $exception) {
            return view('siplah.index', [
                'userId' => $userId,
                'proposals' => collect(),
                'meta' => [],
                'units' => $this->accessibleUnits($request),
                'categories' => self::CATEGORIES,
                'summary' => $this->summary(collect()),
                'error' => $exception->getMessage(),
            ]);
        }

        $proposals = collect($result['data'] ?? [])
            ->when($unitIds !== null, fn ($rows) => $rows->whereIn('work_unit_id', $unitIds))
            ->when($request->filled('category'), fn ($rows) => $rows->filter(
                fn ($proposal) => collect($proposal['items'] ?? [])->contains('category', $request->input('category'))
            ))
            ->values();

        return view('siplah.index', [
            'userId' => $userId,
            'proposals' => $proposals,
            'meta' => $result['meta'] ?? [],
            'units' => $this->accessibleUnits($request),
            'categories' => self::CATEGORIES,
            'summary' => $this->summary($proposals),
            'error' => null,
        ]);
    }

    public function create(Request $request, string $userId)
    {
        $this->authorizeAccess($request);
        $units = $this->accessibleUnits($request);

        return view('siplah.create', [
            'userId' => $userId,
            'units' => $units,
            'autoPhone' => $request->user()?->gtkContact?->no_whatsapp ?: '',
            'autoDate' => now()->toDateString(),
            'externalReference' => 'ALIM-SIPLAH-'.Str::uuid(),
            'categories' => self::CATEGORIES,
        ]);
    }

    public function store(Request $request, string $userId)
    {
        $this->authorizeAccess($request);
        $units = $this->accessibleUnits($request);
        $unitIds = $units->pluck('id')->map(fn ($id) => (string) $id)->all();

        $validated = $this->validateProposal($request, $unitIds);

        $unit = $units->firstWhere('id', $validated['work_unit_id']);
        if (! $unit) {
            return back()->withInput()->with('error', 'Satuan kerja tidak ditemukan.');
        }

        $payload = [
            'external_reference' => $validated['external_reference'],
            'school_id' => $request->attributes->get('schoolContextId'),
            'work_unit_id' => $unit->id,
            'business_unit_code' => $unit->code,
            'requested_by' => $request->user()?->id,
            'requester_name' => $request->user()?->name,
            'requester_phone' => $request->user()?->gtkContact?->no_whatsapp,
            'program_name' => $validated['program_name'] ?? null,
            'purpose' => $validated['purpose'] ?? null,
            'budget_source' => $validated['budget_source'] ?? null,
            'priority' => $validated['priority'],
            'needed_at' => $validated['needed_at'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'items' => $validated['items'],
        ];

        try {
            $proposal = $this->client->createSiplahProposal($payload, $request->file('attachment'));
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('user.siplah.show', ['userId' => $userId, 'number' => $proposal['number']])
            ->with('success', 'Proposal SIPLah dibuat: '.$proposal['number']);
    }

    public function show(Request $request, string $userId, string $number)
    {
        $this->authorizeAccess($request);

        try {
            $proposal = $this->client->showSiplahProposal($number);
            $this->assertUnitAccessible($request, $proposal);
        } catch (RuntimeException $exception) {
            return redirect()->route('user.siplah.index', ['userId' => $userId])
                ->with('error', $exception->getMessage());
        }

        return view('siplah.show', [
            'userId' => $userId,
            'proposal' => $proposal,
            'canApprove' => $this->canApprove($request),
        ]);
    }

    public function action(Request $request, string $userId, string $number, string $action)
    {
        $this->authorizeAccess($request);
        abort_unless(in_array($action, ['submit', 'approve', 'reject', 'cancel', 'send'], true), 404);

        $payload = match ($action) {
            'approve' => [
                'approved_by' => $request->user()?->name,
                'note' => $request->input('note'),
                'approved_total' => $request->input('approved_total'),
            ],
            'reject' => ['reason' => (string) $request->input('reason', 'Ditolak.')],
            'cancel' => ['reason' => (string) $request->input('reason', 'Dibatalkan.')],
            default => [],
        };

        if ($action === 'approve' && ! $this->canApprove($request)) {
            abort(403, 'Anda tidak berwenang menyetujui proposal ini.');
        }

        try {
            $this->client->actionSiplahProposal($number, $action, $payload);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Aksi '.$action.' berhasil.');
    }

    private function summary(Collection $proposals): array
    {
        return [
            'total' => $proposals->count(),
            'draft' => $proposals->where('status', 'draft')->count(),
            'submitted' => $proposals->where('status', 'submitted')->count(),
            'approved' => $proposals->where('status', 'approved')->count(),
            'sent' => $proposals->where('status', 'sent')->count(),
            'processing' => $proposals->whereIn('status', ['processing', 'partially_fulfilled'])->count(),
            'completed' => $proposals->where('status', 'completed')->count(),
            'estimated' => $proposals->sum(fn ($item) => (float) ($item['estimated_total'] ?? 0)),
            'realized' => $proposals->sum(fn ($item) => (float) ($item['realized_total'] ?? 0)),
        ];
    }

    private function validateProposal(Request $request, array $unitIds): array
    {
        return $request->validate([
            'external_reference' => 'required|string|max:120',
            'work_unit_id' => ['required', 'uuid', function ($attribute, $value, $fail) use ($unitIds) {
                if (! in_array((string) $value, $unitIds, true)) {
                    $fail('Satuan kerja tidak dapat diakses.');
                }
            }],
            'program_name' => 'required|string|max:200',
            'purpose' => 'required|string|max:2000',
            'budget_source' => 'nullable|string|max:150',
            'priority' => 'required|in:low,normal,high,urgent',
            'needed_at' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'items' => 'required|array|min:1',
            'items.*.category' => 'nullable|string|max:100',
            'items.*.name' => 'required|string|max:200',
            'items.*.specification' => 'nullable|string|max:2000',
            'items.*.unit' => 'required|string|max:30',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.note' => 'nullable|string|max:500',
        ]);
    }

    private function authorizeAccess(Request $request): void
    {
        $user = $request->user();
        abort_unless(
            $user?->isSuperAdmin() || $user?->hasRole('Satuan Pendidikan'),
            403,
            'Halaman SIPLah hanya untuk Satuan Pendidikan.'
        );
    }

    private function canApprove(Request $request): bool
    {
        $user = $request->user();
        if ($user?->isSuperAdmin()) {
            return true;
        }

        return UserJabatan::hasAny($user, [
            'kepala satuan pendidikan',
            'wakil kepala satuan pendidikan',
            'kepala tata usaha',
            'kepala tu sekolah',
            'tata usaha',
            'staf tata usaha',
        ]);
    }

    private function assertUnitAccessible(Request $request, array $proposal): void
    {
        $unitIds = $this->accessibleUnitIds($request);
        if ($unitIds !== null && ! in_array((string) ($proposal['work_unit_id'] ?? ''), $unitIds, true)) {
            abort(403);
        }
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

    private function accessibleUnitIds(Request $request): ?array
    {
        if ($request->user()?->isSuperAdmin()) {
            return null;
        }

        return $this->accessibleUnits($request)->pluck('id')->map(fn ($id) => (string) $id)->all();
    }
}
