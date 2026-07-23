<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dormitory\StorePermitRequest;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryResident;
use App\Services\Boarding\LeaveWorkflowService;

/**
 * Wizard untuk pengajuan Izin Kepulangan santri.
 * Berisi langkah-langkah: pilih siswa → isi tujuan & keterangan → pilih estimasi waktu → konfirmasi.
 */
class DormitoryWizardController extends Controller
{
    public function __construct(
        private readonly LeaveWorkflowService $leave,
    ) {}

    /**
     * Langkah 1: Pilih santri.
     */
    public function step1(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();

        $residents = DormitoryResident::with(['student.mahroms', 'room'])
            ->where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear?->id)
            ->where('is_active', true)
            ->orderBy('room_id')
            ->get();

        return view('dormitory.wizard.step1', compact('dormitory', 'residents', 'userId', 'activeYear'));
    }

    /**
     * Langkah 2: Tujuan & Keterangan (ditampilkan setelah siswa dipilih via AJAX).
     */
    public function step2(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $student = \App\Models\Student::find($request->input('student_id'));
        $resident = DormitoryResident::where('student_id', $request->input('student_id'))
            ->where('dormitory_id', $asramaUuid)
            ->where('is_active', true)
            ->first();

        return view('dormitory.wizard.step2', compact(
            'dormitory', 'student', 'resident', 'userId', 'asramaUuid'
        ));
    }

    /**
     * Langkah 3: Estimasi waktu & penjemput.
     */
    public function step3(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $student = \App\Models\Student::find($request->input('student_id'));

        return view('dormitory.wizard.step3', compact('dormitory', 'student', 'userId'));
    }

    /**
     * Konfirmasi & submit.
     */
    public function confirm(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();

        $student = \App\Models\Student::find($request->input('student_id'));
        $resident = DormitoryResident::with('room', 'student.mahroms')
            ->where('student_id', $request->input('student_id'))
            ->where('dormitory_id', $asramaUuid)
            ->where('is_active', true)
            ->first();

        return view('dormitory.wizard.confirm', compact(
            'dormitory', 'student', 'resident', 'userId', 'activeYear'
        ));
    }

    /**
     * Simpan wizard data ke session untuk navigasi balik.
     */
    public function saveStep(Request $request, string $asramaUuid)
    {
        $validated = $request->validate([
            'student_id' => 'required_if:step,1|sometimes|exists:students,id',
            'permit_type' => 'required_if:step,2|in:pulang,keluar_kota,berobat,keperluan_keluarga,lainnya,sakit',
            'destination' => 'required_if:step,2|sometimes|string|max:191',
            'purpose' => 'sometimes|string',
            'companion_name' => 'sometimes|string|max:191',
            'companion_relation' => 'sometimes|string|max:100',
            'companion_phone' => 'sometimes|string|max:20',
            'departure_datetime' => 'required_if:step,3|date',
            'expected_return_datetime' => 'required_if:step,3|date|after:departure_datetime',
            'notes' => 'sometimes|string',
        ]);

        $step = $request->input('step', '1');

        // Accumulate in session
        $wizardData = session("wizard.{$asramaUuid}", []);
        $wizardData[$step] = $validated;

        session(["wizard.{$asramaUuid}" => $wizardData]);

        return response()->json(['success' => true]);
    }

    /**
     * Final submission dari wizard.
     */
    public function submitWizard(Request $request, string $userId, string $asramaUuid)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'permit_type' => 'required|in:pulang,keluar_kota,berobat,keperluan_keluarga,lainnya,sakit',
            'destination' => 'nullable|string|max:191',
            'purpose' => 'nullable|string',
            'departure_datetime' => 'required|date',
            'expected_return_datetime' => 'required|date|after:departure_datetime',
            'companion_name' => 'nullable|string|max:191',
            'companion_relation' => 'nullable|string|max:100',
            'companion_phone' => 'nullable|string|max:20',
            'companion_is_mahrom' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $activeYear = AcademicYear::where('is_active', true)->first();
        $dormitory = Dormitory::findOrFail($asramaUuid);

        // Ambil mahrom_id jika companion_is_mahrom
        if ($request->input('companion_is_mahrom')) {
            $data['mahrom_id'] = $request->input('companion_id');
        }

        try {
            $this->leave->submit(
                data: $data,
                dormitoryId: $asramaUuid,
                activeYearId: $activeYear?->id ?? $activeYear->first()?->id,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['permit_type' => $e->getMessage()]);
        }

        // Clear session
        session()->forget("wizard.{$asramaUuid}");

        return redirect()->route('user.asrama.dormitory-returns.index', [
            'userId' => $userId, 'asramaUuid' => $asramaUuid
        ])->with('success', 'Izin kepulangan berhasil diajukan dan menunggu persetujuan.');
    }
}
