<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SarprasDisposalController extends SarprasBaseController
{
    public function __construct()
    {
        $this->authorizeResource(Asset::class, 'asset');
    }

    public function pending()
    {
        $candidates = Asset::where('condition', 'dihapus')
            ->orWhere(fn ($q) => $q->whereNotNull('disposal_date')->whereNull('disposal_reason'))
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get();

        return view('sarpras.disposal.pending', compact('candidates'));
    }

    public function approve(Asset $asset, Request $request): RedirectResponse|JsonResponse
    {
        if (! canPermission('sarpras_disposal_approve')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $data = $request->validate([
            'disposal_method' => 'required|in:sell,scrap,transfer,donate,destroy',
            'disposal_reason' => 'required|string|max:500',
            'disposal_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($asset, $data) {
            $previousCondition = $asset->condition;

            $asset->update([
                'condition' => 'dihapus',
                'is_active' => false,
                'disposal_method' => $data['disposal_method'],
                'disposal_value' => $data['disposal_value'] ?? null,
                'disposal_date' => now(),
                'disposal_reason' => $data['disposal_reason'],
            ]);

            // Log to asset events
            $logger = app(\App\Services\Sarpras\AssetEventLogger::class);
            $logger->logAssetStatusChanged(
                $asset,
                $previousCondition ?: 'active',
                'dihapus',
                $data['disposal_reason']
            );
        });

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Aset telah dihapus dari inventaris.');
    }

    public function reject(Asset $asset, Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $previousCondition = $asset->condition;
        $asset->update([
            'disposal_reason' => null,
            'disposal_method' => null,
            'disposal_date' => null,
        ]);

        $logger = app(\App\Services\Sarpras\AssetEventLogger::class);
        $logger->logAssetStatusChanged(
            $asset,
            'disposal_proposed',
            'active',
            'Rejected disposal: '.$data['reason']
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Penolakan penghapusan aset dicatat.');
    }

    public function recordSale(Asset $asset, Request $request): JsonResponse
    {
        if (! in_array($asset->condition, ['dihapus', null], true)) {
            return response()->json([
                'success' => false,
                'error' => 'Aset tidak dalam status disposisi.',
            ], 422);
        }

        $data = $request->validate([
            'sale_price' => 'required|numeric|min:0',
            'buyer_name' => 'required|string|max:255',
            'sale_date' => 'required|date',
            'receipt_no' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($asset, $data) {
            $asset->update([
                'disposal_value' => $data['sale_price'],
                'disposal_date' => $data['sale_date'],
                'disposal_reason' => 'Sold to '.$data['buyer_name'].' ('.($data['receipt_no'] ?? '-').')',
                'is_active' => false,
                'condition' => 'dihapus',
            ]);

            $logger = app(\App\Services\Sarpras\AssetEventLogger::class);
            $logger->logAssetStatusChanged(
                $asset,
                'disposed',
                'sold',
                'Asset sold to '.$data['buyer_name']
            );
        });

        return response()->json(['success' => true]);
    }
}
