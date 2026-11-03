<?php

namespace App\Http\Controllers\Uks;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\UksStudentInsurance;
use Illuminate\Http\Request;

/**
 * Asuransi / BPJS santri — kepesertaan per santri beserta masa berlaku.
 */
class InsuranceController extends Controller
{
    public const PROVIDERS = [
        'BPJS Kesehatan' => 'BPJS Kesehatan',
        'BPJS Ketenagakerjaan' => 'BPJS Ketenagakerjaan',
        'Asuransi Sekolah' => 'Asuransi Sekolah',
        'Lainnya' => 'Lainnya',
    ];

    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');

        $insurances = UksStudentInsurance::query()
            ->with('student:id,name,nis,nisn,school_id')
            ->when($schoolId, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('school_id', $schoolId)))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('card_number', 'like', "%{$search}%")
                        ->orWhere('provider', 'like', "%{$search}%")
                        ->orWhereHas('student', function ($s) use ($search) {
                            $s->where('name', 'like', "%{$search}%")
                                ->orWhere('nis', 'like', "%{$search}%")
                                ->orWhere('nisn', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $students = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'nis', 'nisn']);

        $studentQuery = Student::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        $stats = [
            'total' => UksStudentInsurance::when($schoolId, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('school_id', $schoolId)))->count(),
            'aktif' => UksStudentInsurance::active()->when($schoolId, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('school_id', $schoolId)))->count(),
            'kedaluwarsa' => UksStudentInsurance::when($schoolId, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('school_id', $schoolId)))
                ->whereNotNull('valid_until')->whereDate('valid_until', '<', now()->toDateString())->count(),
            'santri' => $studentQuery->count(),
        ];

        return view('uks.insurance.index', [
            'userId' => $userId,
            'insurances' => $insurances,
            'students' => $students,
            'search' => $search,
            'status' => $status,
            'stats' => $stats,
            'providers' => self::PROVIDERS,
            'statusOptions' => UksStudentInsurance::STATUS_OPTIONS,
        ]);
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'provider' => 'required|string|max:80',
            'card_number' => 'nullable|string|max:60',
            'class_type' => 'nullable|string|max:30',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'status' => 'required|in:'.implode(',', array_keys(UksStudentInsurance::STATUS_OPTIONS)),
            'notes' => 'nullable|string|max:255',
        ]);

        UksStudentInsurance::create($validated);

        return redirect()
            ->route('user.uks.insurance.index', ['userId' => $userId])
            ->with('success', 'Data asuransi ditambahkan.');
    }

    public function update(Request $request, string $userId, string $insuranceId)
    {
        $validated = $request->validate([
            'provider' => 'required|string|max:80',
            'card_number' => 'nullable|string|max:60',
            'class_type' => 'nullable|string|max:30',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'status' => 'required|in:'.implode(',', array_keys(UksStudentInsurance::STATUS_OPTIONS)),
            'notes' => 'nullable|string|max:255',
        ]);

        UksStudentInsurance::findOrFail($insuranceId)->update($validated);

        return redirect()
            ->route('user.uks.insurance.index', ['userId' => $userId])
            ->with('success', 'Data asuransi diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $insuranceId)
    {
        UksStudentInsurance::findOrFail($insuranceId)->delete();

        return redirect()
            ->route('user.uks.insurance.index', ['userId' => $userId])
            ->with('success', 'Data asuransi dihapus.');
    }
}
