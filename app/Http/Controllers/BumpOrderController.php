<?php

namespace App\Http\Controllers;

use App\Models\BumpOrderCache;
use App\Models\BumpOrderCacheLog;
use App\Models\School;
use App\Models\WorkUnit;
use App\Services\Pos\PosProcurementClient;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Pemesanan barang BUMP: ALIM hanya UI + cache riwayat; data resmi di BUMP.
 */
class BumpOrderController extends Controller
{
    public function __construct(private readonly PosProcurementClient $client) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeAccess($request);
        $unitIds = $this->accessibleUnitIds($request);

        $scope = BumpOrderCache::query()
            ->when($unitIds !== null, fn ($q) => $q->whereIn('work_unit_id', $unitIds));

        $orders = (clone $scope)
            ->with('workUnit:id,name,code')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => (clone $scope)->count(),
            'pending' => (clone $scope)->whereIn('status', ['pending', 'confirmed', 'processing'])->count(),
            'completed' => (clone $scope)->where('status', 'completed')->count(),
            'unpaid' => (clone $scope)->where('payment_status', 'unpaid')->count(),
            'value' => (float) (clone $scope)->sum('total'),
        ];

        return view('bump-orders.index', compact('userId', 'orders', 'stats'));
    }

    public function create(Request $request, string $userId)
    {
        $this->authorizeAccess($request);
        $units = $this->accessibleUnits($request);
        $search = trim((string) $request->input('search', ''));
        $products = [];
        $paymentMethods = [];
        $error = null;

        try {
            $cacheKey = 'bump_catalog_'.md5(strtolower($search));
            $products = Cache::remember($cacheKey, 300, fn () => $this->client->products(array_filter([
                'search' => $search !== '' ? $search : null,
                'per_page' => 200,
            ]))['data'] ?? []);
            $paymentMethods = Cache::remember('bump_payment_methods', 600, fn () => $this->client->bumpPaymentMethods());
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }

        return view('bump-orders.create', [
            'userId' => $userId,
            'units' => $units,
            'products' => $products,
            'paymentMethods' => $paymentMethods,
            'search' => $search,
            'error' => $error,
            'autoPhone' => $request->user()?->gtkContact?->no_whatsapp ?: '',
            'autoDate' => now()->toDateString(),
            'requesterName' => $request->user()?->name,
            'requesterJabatan' => $request->user()?->employment?->jabatan
                ?: $request->user()?->gtkWorkUnits()->where('is_primary', true)->value('jabatan'),
        ]);
    }

    public function store(Request $request, string $userId)
    {
        $this->authorizeAccess($request);
        $units = $this->accessibleUnits($request);
        $unitIds = $units->pluck('id')->map(fn ($id) => (string) $id)->all();

        $validated = $request->validate([
            'work_unit_id' => ['required', 'uuid', function ($attribute, $value, $fail) use ($unitIds) {
                if (! in_array((string) $value, $unitIds, true)) {
                    $fail('Satuan kerja tidak dapat diakses.');
                }
            }],
            'payment_method' => 'required|string|max:30',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.qty' => 'required|numeric|min:0.01',
        ]);

        $unit = $units->firstWhere('id', $validated['work_unit_id']);
        if (! $unit) {
            return back()->withInput()->with('error', 'Satuan kerja tidak ditemukan.');
        }

        $external = 'ALIM-BUMP-'.Str::uuid();

        try {
            $remote = $this->client->createBumpOrder([
                'external_reference' => $external,
                'satuan_kerja_id' => $unit->id,
                'business_unit_code' => $unit->code,
                'requested_by' => $request->user()?->id,
                'requester_name' => $request->user()?->name,
                'requester_phone' => $request->user()?->gtkContact?->no_whatsapp,
                'payment_method' => $validated['payment_method'],
                'items' => collect($validated['items'])->map(fn ($i) => [
                    'product_id' => (int) $i['product_id'],
                    'qty' => (float) $i['qty'],
                ])->values()->all(),
            ]);
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $cache = BumpOrderCache::updateOrCreate(
            ['external_reference' => $external],
            [
                'user_id' => $request->user()?->id,
                'work_unit_id' => $unit->id,
                'school_id' => $request->attributes->get('schoolContextId'),
                'pos_order_number' => $remote['number'],
                'payment_method' => $validated['payment_method'],
                'status' => $remote['status'] ?? 'pending',
                'payment_status' => $remote['payment_status'] ?? 'unpaid',
                'total' => $remote['total'] ?? 0,
                'payload' => $remote,
                'last_synced_at' => now(),
            ]
        );
        $this->recordLog($cache, $remote, 'Pesanan dibuat dari ALIM.');

        return redirect()->route('user.bump-orders.show', [
            'userId' => $userId, 'number' => $cache->pos_order_number,
        ])->with('success', 'Pesanan BUMP dibuat: '.$cache->pos_order_number.'. Stok direservasi oleh BUMP.');
    }

    public function show(Request $request, string $userId, string $number)
    {
        $this->authorizeAccess($request);
        $unitIds = $this->accessibleUnitIds($request);
        $cache = BumpOrderCache::where('pos_order_number', $number)
            ->when($unitIds !== null, fn ($q) => $q->whereIn('work_unit_id', $unitIds))
            ->firstOrFail();

        $warning = null;
        try {
            $remote = $this->client->bumpOrder($number);
            $cache->update([
                'status' => $remote['status'] ?? $cache->status,
                'payment_status' => $remote['payment_status'] ?? $cache->payment_status,
                'total' => $remote['total'] ?? $cache->total,
                'payload' => $remote,
                'last_synced_at' => now(),
            ]);
            $cache->refresh();
            $this->recordLog($cache, $remote, 'Sinkron status dari BUMP.');
        } catch (RuntimeException $exception) {
            $warning = $exception->getMessage();
        }

        return view('bump-orders.show', compact('userId', 'cache', 'warning'));
    }

    public function cancel(Request $request, string $userId, string $number)
    {
        $this->authorizeAccess($request);
        $unitIds = $this->accessibleUnitIds($request);
        $cache = BumpOrderCache::where('pos_order_number', $number)
            ->when($unitIds !== null, fn ($q) => $q->whereIn('work_unit_id', $unitIds))
            ->firstOrFail();

        try {
            $remote = $this->client->cancelBumpOrder($number, (string) $request->input('reason', 'Dibatalkan dari ALIM.'));
            $cache->update([
                'status' => $remote['status'] ?? 'cancelled',
                'payload' => $remote,
                'last_synced_at' => now(),
            ]);
            $this->recordLog($cache, $remote, 'Dibatalkan dari ALIM.');
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Pesanan dibatalkan dan reservasi stok dilepas.');
    }

    private function recordLog(BumpOrderCache $cache, array $remote, string $note): void
    {
        $last = $cache->logs()->first();
        if ($last && $last->status === ($remote['status'] ?? null) && $last->payment_status === ($remote['payment_status'] ?? null)) {
            return;
        }

        BumpOrderCacheLog::create([
            'bump_order_cache_id' => $cache->id,
            'status' => $remote['status'] ?? $cache->status,
            'payment_status' => $remote['payment_status'] ?? $cache->payment_status,
            'source' => 'bump',
            'note' => $note,
        ]);
    }

    private function authorizeAccess(Request $request): void
    {
        $user = $request->user();
        abort_unless($user?->isSuperAdmin() || $user?->hasRole('Satuan Pendidikan'), 403, 'Halaman pemesanan BUMP hanya untuk Satuan Pendidikan.');
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

        return WorkUnit::query()->whereIn('id', $ids)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
    }

    private function accessibleUnitIds(Request $request): ?array
    {
        if ($request->user()?->isSuperAdmin()) {
            return null;
        }

        return $this->accessibleUnits($request)->pluck('id')->map(fn ($id) => (string) $id)->all();
    }
}
