<?php

namespace App\Http\Controllers\Sarpras;

use App\Http\Controllers\Controller;
use App\Http\Requests\VendorStoreRequest;
use App\Models\Vendor;
use App\Models\VendorCategory;
use App\Models\VendorContact;
use App\Models\VendorAddress;
use App\Models\VendorBank;
use App\Models\VendorTax;
use App\Services\Sarpras\VendorPerformanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SarprasVendorController extends Controller
{
    public function __construct(protected VendorPerformanceService $performance) {}

    public function index(Request $request)
    {
        $query = Vendor::query()->with(['category', 'contacts', 'addresses']);

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('vendor_code', 'like', "%{$q}%")
                  ->orWhere('legal_name', 'like', "%{$q}%")
                  ->orWhere('npwp', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('vendor_type')) {
            $query->where('vendor_type', $request->input('vendor_type'));
        }

        $vendors = $query->orderBy('name')
            ->paginate($request->input('per_page', 25));

        return view('sarpras.vendor.index', [
            'vendors' => $vendors,
            'categories' => VendorCategory::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('sarpras.vendor.create', [
            'categories' => VendorCategory::orderBy('name')->get(),
        ]);
    }

    public function store(VendorStoreRequest $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $data = $request->validated();
                $data['created_by'] = auth()->id();
                $vendor = Vendor::create(collect($data)->except(['contacts', 'addresses', 'banks', 'tax'])->toArray());

                if (!empty($data['contacts'])) {
                    foreach ($data['contacts'] as $c) {
                        $vendor->contacts()->create($c);
                    }
                }
                if (!empty($data['addresses'])) {
                    foreach ($data['addresses'] as $a) {
                        $vendor->addresses()->create($a);
                    }
                }
                if (!empty($data['banks'])) {
                    foreach ($data['banks'] as $b) {
                        $vendor->banks()->create($b);
                    }
                }
                if (!empty($data['tax'])) {
                    $vendor->tax()->create($data['tax']);
                }
            });

            return redirect()->route('sarpras.vendor.index')->with('success', 'Vendor berhasil ditambahkan');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function show(Vendor $vendor)
    {
        $vendor->load([
            'category', 'contacts', 'addresses', 'banks', 'tax',
            'contracts', 'warranties', 'slas', 'documents', 'ratings',
        ]);

        $performance = $this->performance->computeMetrics(
            $vendor,
            now()->subYear()->toDateString(),
            now()->toDateString()
        );

        return view('sarpras.vendor.show', [
            'vendor' => $vendor,
            'performance' => $performance,
        ]);
    }

    public function edit(Vendor $vendor)
    {
        return view('sarpras.vendor.edit', [
            'vendor' => $vendor->load(['contacts', 'addresses', 'banks', 'tax']),
            'categories' => VendorCategory::orderBy('name')->get(),
        ]);
    }

    public function update(VendorStoreRequest $request, Vendor $vendor)
    {
        try {
            DB::transaction(function () use ($request, $vendor) {
                $vendor->update($request->validated());
            });
            return redirect()->route('sarpras.vendor.show', $vendor)->with('success', 'Vendor diperbarui');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function destroy(Vendor $vendor)
    {
        try {
            if ($vendor->purchaseOrders()->whereIn('status', ['received', 'partial', 'approved'])->exists()) {
                return back()->with('error', 'Tidak dapat menghapus vendor dengan PO aktif.');
            }
            $vendor->delete();
            return redirect()->route('sarpras.vendor.index')->with('success', 'Vendor dihapus');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function rank(Request $request)
    {
        $rankings = $this->performance->rankings($request->input('limit', 10));
        return view('sarpras.vendor.rank', [
            'rankings' => $rankings,
        ]);
    }
}