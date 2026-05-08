<?php

namespace App\Http\Controllers;

use App\Services\ApprovalService;
use Illuminate\Http\Request;
use App\Models\GtkRecruitment;
use Illuminate\Support\Facades\Auth;

class GtkRecruitmentController extends Controller
{
    public function store(Request $request, ApprovalService $approvalService)
    {
        $data = $request->validate([
            'work_unit_id' => 'required|exists:work_units,id',
            'jabatan' => 'required|string',
            'kebutuhan' => 'required|integer|min:1',
            'kualifikasi' => 'required|string',
            'tanggal_dibutuhkan' => 'required|date',
        ]);

        $recruitment = GtkRecruitment::create([
            ...$data,
            'created_by' => Auth::id(),
            'status' => 'submitted',
        ]);

        // 🚀 START APPROVAL FLOW
        $approvalService->start($recruitment, 'recruitment_gtk');

        return response()->json([
            'message' => 'Pengajuan recruitment berhasil',
            'data' => $recruitment,
        ], 201);
    }
}
