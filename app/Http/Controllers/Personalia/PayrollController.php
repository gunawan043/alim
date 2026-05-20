<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function index(Request $request, string $userId)
    {
        return view('personalia.payroll.index', compact('userId'));
    }

    public function create(Request $request, string $userId)
    {
        return view('personalia.payroll.create', compact('userId'));
    }

    public function store(Request $request, string $userId)
    {
        // TODO: Implement store logic
        return redirect()->back()->with('success', 'Data payroll berhasil disimpan.');
    }

    public function edit(Request $request, string $userId, string $id)
    {
        return view('personalia.payroll.edit', compact('userId', 'id'));
    }

    public function update(Request $request, string $userId, string $id)
    {
        // TODO: Implement update logic
        return redirect()->back()->with('success', 'Data payroll berhasil diupdate.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        // TODO: Implement destroy logic
        return redirect()->back()->with('success', 'Data payroll berhasil dihapus.');
    }

    public function potongan(Request $request, string $userId)
    {
        return view('personalia.payroll.potongan', compact('userId'));
    }

    public function tunjangan(Request $request, string $userId)
    {
        return view('personalia.payroll.tunjangan', compact('userId'));
    }

    public function bpjstk(Request $request, string $userId)
    {
        return view('personalia.payroll.bpjstk', compact('userId'));
    }

    public function bpjsKes(Request $request, string $userId)
    {
        return view('personalia.payroll.bpjs-kes', compact('userId'));
    }

    public function settings(Request $request, string $userId)
    {
        return view('personalia.payroll.settings', compact('userId'));
    }

    public function datatable(Request $request, string $userId)
    {
        // TODO: Return JSON datatable response
        return response()->json(['data' => []]);
    }

    // Slip Gaji
    public function slipIndex(Request $request, string $userId)
    {
        return view('personalia.payroll.slip.index', compact('userId'));
    }

    public function slipShow(Request $request, string $userId, string $id)
    {
        return view('personalia.payroll.slip.show', compact('userId', 'id'));
    }

    public function slipPdf(Request $request, string $userId, string $id)
    {
        // TODO: Generate PDF slip gaji
        return response()->download('dummy.pdf');
    }
}