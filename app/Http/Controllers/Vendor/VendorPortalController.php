<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\ProcurementRequest;
use App\Models\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorPortalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:vendor');
    }

    public function index(Request $request): View
    {
        $vendor = $request->user();
        $vendorName = $vendor->name;

        // Total procurement orders
        $totalOrders = ProcurementRequest::where('vendor_name', $vendorName)->count();

        // Pending deliveries (ordered but not yet delivered/completed)
        $pendingDeliveries = ProcurementRequest::where('vendor_name', $vendorName)
            ->whereIn('status', ['ordered', 'delivered'])
            ->count();

        // Repair orders (if any linked to vendor)
        $repairOrders = WorkOrder::whereHas('repairRequest', function ($q) use ($vendor) {
            $q->where('asset.supplier_name', $vendor->name);
        })->count();

        // Recent orders
        $recentOrders = ProcurementRequest::where('vendor_name', $vendorName)
            ->with('items')
            ->orderByDesc('request_date')
            ->take(5)
            ->get();

        // Upcoming deliveries (ordered with delivery_date in future)
        $upcomingDeliveries = ProcurementRequest::where('vendor_name', $vendorName)
            ->where('status', 'ordered')
            ->where('delivery_date', '>=', now())
            ->count();

        return view('vendor.dashboard', compact(
            'vendorName',
            'totalOrders',
            'pendingDeliveries',
            'repairOrders',
            'recentOrders',
            'upcomingDeliveries'
        ));
    }

    public function orders(Request $request): View
    {
        $vendor = $request->user();
        $vendorName = $vendor->name;

        $orders = ProcurementRequest::where('vendor_name', $vendorName)
            ->with('items')
            ->orderByDesc('request_date')
            ->paginate(15);

        return view('vendor.orders.index', compact('orders'));
    }

    public function orderShow(Request $request, string $id): View
    {
        $vendor = $request->user();
        $order = ProcurementRequest::with('items')
            ->where('id', $id)
            ->where('vendor_name', $vendor->name)
            ->firstOrFail();

        return view('vendor.orders.show', compact('order'));
    }

    public function orderUpdateStatus(Request $request, string $id): RedirectResponse
    {
        $vendor = $request->user();
        $order = ProcurementRequest::where('id', $id)
            ->where('vendor_name', $vendor->name)
            ->firstOrFail();

        $validated = $request->validate([
            'status' => 'required|in:ordered,delivered',
            'delivery_date' => 'nullable|date|after_or_equal:today',
            'tracking_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $order->update($validated);

        return back()->with('success', 'Status pesanan berhasil diperbarui.');
    }

    public function invoices(Request $request): View
    {
        $vendor = $request->user();
        $vendorName = $vendor->name;

        $invoices = \App\Models\VendorInvoice::whereHas('vendor', function ($q) use ($vendorName) {
            $q->where('name', $vendorName);
        })
            ->orderByDesc('invoice_date')
            ->paginate(15);

        return view('vendor.invoices.index', compact('invoices'));
    }

    public function performance(Request $request): View
    {
        $vendor = $request->user();
        $vendorName = $vendor->name;

        $vendorModel = \App\Models\Vendor::where('name', $vendorName)->first();

        $totalDeliveries = ProcurementRequest::where('vendor_name', $vendorName)->count();
        $onTimeDeliveries = ProcurementRequest::where('vendor_name', $vendorName)
            ->whereNotNull('received_date')
            ->whereColumn('received_date', '<=', 'delivery_date')
            ->count();

        $performance = [
            'total_orders' => $totalDeliveries,
            'completed_orders' => ProcurementRequest::where('vendor_name', $vendorName)
                ->whereIn('status', ['completed', 'delivered'])->count(),
            'on_time_rate' => $totalDeliveries > 0
                ? round(($onTimeDeliveries / $totalDeliveries) * 100, 1)
                : 0,
            'total_invoices' => $vendorModel ? \App\Models\VendorInvoice::where('vendor_id', $vendorModel->id)->count() : 0,
            'total_revenue' => $vendorModel ? \App\Models\VendorInvoice::where('vendor_id', $vendorModel->id)->sum('total') : 0,
        ];

        return view('vendor.performance', compact('performance'));
    }
}
