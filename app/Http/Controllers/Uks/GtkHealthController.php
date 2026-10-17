<?php

namespace App\Http\Controllers\Uks;

use App\Http\Controllers\Controller;
use App\Models\GtkHealthRecord;
use App\Models\GtkMedicalHistory;
use App\Models\GtkVaccination;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * GtkHealthController — manage GTK health data in UKS satuan kerja.
 *
 * Access rules:
 *   - Kepala UKS       → sees ALL GTK gtkProfiles with health data
 *   - Admin UKS        → semua GTK UKS tanpa filter gender
 */
class GtkHealthController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        $roles = $currentUser->getRoleNames();
        $schoolId = $request->attributes->get('schoolContextId');

        $query = User::whereHas('gtkProfile')
            ->with(['gtkProfile', 'employments']);

        // Filter by school context
        if ($schoolId) {
            $query->whereHas('employments', fn ($e) => $e->where('school_id', $schoolId));
        }

        // Search
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($sq) => $sq
                ->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%"));
        }

        // Filter by golongan darah
        if ($request->filled('blood_type')) {
            $query->whereHas('gtkProfile', fn ($q) => $q->where('golongan_darah', $request->blood_type));
        }

        $usersCollection = $query->get(); // collect for stats before pagination
        $users = $query->orderBy('name')->paginate(20)->withQueryString();

        // Pre-calculate statistics for stat cards
        $totalUsers = $usersCollection->count();
        $putraCount = $usersCollection->where(function ($u) {
            return $u->gtkProfile && $u->gtkProfile->jenis_kelamin === 'L';
        })->count();
        $putriCount = $usersCollection->where(function ($u) {
            return $u->gtkProfile && $u->gtkProfile->jenis_kelamin === 'P';
        })->count();

        $bloodTypeCounts = [];
        $totalWithBloodType = 0;
        foreach ($usersCollection as $u) {
            if ($u->gtkProfile && $u->gtkProfile->golongan_darah) {
                $bt = $u->gtkProfile->golongan_darah;
                $bloodTypeCounts[$bt] = ($bloodTypeCounts[$bt] ?? 0) + 1;
                $totalWithBloodType++;
            }
        }

        return view('uks.gtk-health.index', [
            'gtkList' => $users,
            'genderFilter' => $genderFilter,
            'allowedBloodTypes' => ['A', 'B', 'AB', 'O'],
            'statistics' => [
                'total' => $totalUsers,
                'putra' => $putraCount,
                'putri' => $putriCount,
                'blood_type_counts' => $bloodTypeCounts,
                'total_with_blood_type' => $totalWithBloodType,
            ],
        ]);
    }

    /**
     * Halaman profil kesehatan GTK untuk sendiri (user yang sedang login).
     * Menggunakan method show() dengan parameter gtkUuid = auth user id.
     */
    public function selfProfile()
    {
        return $this->show(auth()->id());
    }

    public function show(string $gtkUuid)
    {
        $currentUser = auth()->user();
        $roles = $currentUser->getRoleNames();

        $user = User::with(['gtkProfile'])->where('id', $gtkUuid)->firstOrFail();

        // ── New GTK Health Data ───────────────────────────────────
        $healthRecords = GtkHealthRecord::forUser($user->id);
        $latestRecord = GtkHealthRecord::latestForUser($user->id);
        $medicalHistory = GtkMedicalHistory::forUser($user->id);
        $vaccinations = GtkVaccination::where('user_id', $user->id)
            ->with('administeredBy')
            ->orderByDesc('given_at')
            ->get();

        $staffQuery = User::whereHas('gtkProfile')
            ->with('gtkProfile')
            ->orderBy('name');

        $gtkStaff = $staffQuery->get()->map(function ($u) {
            $u->gender_label = $u->gtkProfile?->jenis_kelamin === 'L' ? 'L' : ($u->gtkProfile?->jenis_kelamin === 'P' ? 'P' : '?');
            $u->blood_label = $u->gtkProfile?->golongan_darah ?? '-';

            return $u;
        });

        return view('uks.gtk-health.show', [
            'user' => $user,
            'profile' => $user->gtkProfile,
            'healthRecords' => $healthRecords,
            'latestRecord' => $latestRecord,
            'medicalHistory' => $medicalHistory,
            'vaccinations' => $vaccinations,
            'gtkStaff' => $gtkStaff,
        ]);
    }

    /**
     * Update GTK gtkProfile health data (golongan darah, jenis kelamin, dll).
     */
    public function update(Request $request, string $gtkUuid)
    {
        $currentUser = auth()->user();
        $roles = $currentUser->getRoleNames();

        $user = User::with(['gtkProfile'])->findOrFail($gtkUuid);

        $validated = $request->validate([
            'jenis_kelamin' => ['sometimes', 'in', 'L', 'P'],
            'golongan_darah' => ['nullable', 'in', 'A', 'B', 'AB', 'O'],
            'tekanan_darah' => 'nullable|string|max:20',
            'pulse' => 'nullable|integer|min:30|max:250',
            'tinggi_badan' => 'nullable|numeric|min:30|max:250',
            'berat_badan' => 'nullable|numeric|min:10|max:300',
            'waist_circumference' => 'nullable|numeric|min:30|max:250',
            'alergi' => 'nullable|string|max:500',
            'riwayat_penyakit' => 'nullable|string|max:1000',
            'ongoing_medication' => 'nullable|string|max:500',
            'p3k' => 'nullable|string|max:1000',
            'cholesterol_total' => 'nullable|numeric|min:50|max:500',
            'triglycerides' => 'nullable|numeric|min:20|max:1000',
            'blood_sugar_fasting' => 'nullable|numeric|min:20|max:600',
            'uric_acid' => 'nullable|numeric|min:1|max:15',
            'hemoglobin' => 'nullable|numeric|min:4|max:25',
            'temperature' => 'nullable|numeric|min:34|max:45',
        ]);

        if (! $user->gtkProfile) {
            User::find($gtkUuid)->gtkProfile()->create($validated);
        } else {
            $user->gtkProfile->update($validated);
        }

        return redirect()->route('user.uks.gtk-health.show', ['userId' => auth()->user()->id, 'gtkUuid' => $user->id])
            ->with('success', 'Data kesehatan GTK berhasil diperbarui.');
    }

    /**
     * Store a new GTK health record (check-up / MCU).
     */
    public function storeRecord(Request $request, string $gtkUuid)
    {
        $user = User::with(['gtkProfile'])->findOrFail($gtkUuid);

        // Role-based check
        if ($user->gtkProfile) {

        }

        $validated = $request->validate([
            'check_date' => 'required|date',
            'weight' => 'nullable|numeric|min:10|max:300',
            'height' => 'nullable|numeric|min:50|max:250',
            'waist_circumference' => 'nullable|numeric|min:30|max:250',
            'blood_pressure' => 'nullable|string|max:20',
            'pulse' => 'nullable|integer|min:30|max:250',
            'temperature' => 'nullable|numeric|min:34|max:45',
            'respiration_rate' => 'nullable|numeric|min:8|max:60',
            'oxygen_saturation' => 'nullable|numeric|min:70|max:100',
            // Labs
            'blood_sugar_fasting' => 'nullable|numeric|min:20|max:600',
            'blood_sugar_random' => 'nullable|numeric|min:20|max:600',
            'uric_acid' => 'nullable|numeric|min:1|max:15',
            'hemoglobin' => 'nullable|numeric|min:4|max:25',
            'cholesterol_total' => 'nullable|numeric|min:50|max:500',
            'triglycerides' => 'nullable|numeric|min:20|max:1000',
            'sgot_ast' => 'nullable|numeric|min:1|max:500',
            'sgpt_alt' => 'nullable|numeric|min:1|max:500',
            'creatinine' => 'nullable|numeric|min:0.1|max:15',
            'bun' => 'nullable|numeric|min:1|max:100',
            // Physical exam details
            'right_eye_vision' => 'nullable|string|max:10',
            'left_eye_vision' => 'nullable|string|max:10',
            'peak_flow' => 'nullable|integer|min:100|max:800',
            // Lifestyle
            'smoking_status' => 'nullable|in:tidak_pernah,mantan,aktif',
            'physical_activity' => 'nullable|in:jarang,sedang,sering',
            // Findings
            'complaints' => 'nullable|string|max:2000',
            'physical_examination' => 'nullable|string|max:2000',
            'diagnosis' => 'nullable|string|max:2000',
            'recommendation' => 'nullable|string|max:2000',
            'fitness_status' => 'nullable|in:sehat,sehat_dengan_catatan,belum_sehat',
            'referred_to_faskes' => 'nullable|boolean',
            'referral_reason' => 'nullable|string|max:500',
        ]);

        // Auto-compute BMI
        if (! empty($validated['height']) && ! empty($validated['weight'])) {
            $hM = $validated['height'] / 100;
            $validated['bmi'] = round($validated['weight'] / ($hM * $hM), 2);
        }

        // Source & recorded_by
        $validated['source'] = $validated['source'] ?? 'mcu';
        $validated['recorded_by'] = $validated['recorded_by'] ?? auth()->id();

        GtkHealthRecord::create($validated);

        return back()->with('success', 'Data pemeriksaan kesehatan GTK berhasil disimpan.');
    }

    /**
     * Show health records history for a GTK member.
     */
    public function showRecords(string $gtkUuid)
    {
        $currentUser = auth()->user();
        $roles = $currentUser->getRoleNames();

        $user = User::with(['gtkProfile'])->findOrFail($gtkUuid);

        // Role-based access control for records page
        if ($user->gtkProfile) {
        }

        $records = GtkHealthRecord::where('user_id', $user->id)
            ->orderByDesc('examined_at')
            ->paginate(20);

        $latest = GtkHealthRecord::where('user_id', $user->id)->orderByDesc('examined_at')->first();
        $medicalHistory = GtkMedicalHistory::where('user_id', $user->id)->first();
        $vaccinations = GtkVaccination::where('user_id', $user->id)
            ->with('administeredBy')
            ->orderByDesc('given_at')
            ->get();

        $profile = $user->gtkProfile;

        $staffQuery = User::whereHas('gtkProfile')
            ->with('gtkProfile')
            ->orderBy('name');

        $gtkStaff = $staffQuery->get()->map(function ($u) {
            $u->gender_label = $u->gtkProfile?->jenis_kelamin === 'L' ? 'L' : ($u->gtkProfile?->jenis_kelamin === 'P' ? 'P' : '?');
            $u->blood_label = $u->gtkProfile?->golongan_darah ?? '-';

            return $u;
        });

        return view('uks.gtk-health.records', compact(
            'user', 'profile', 'records', 'latest', 'medicalHistory', 'vaccinations', 'gtkStaff'
        ));
    }

    /**
     * Store/update medical history for a GTK member.
     */
    public function storeMedicalHistory(Request $request, string $gtkUuid)
    {
        $user = User::findOrFail($gtkUuid);

        $validated = $request->validate([
            // Diseases
            'hypertension' => 'nullable|in:ya,tidak,diketahui',
            'diabetes' => 'nullable|in:ya,tidak,diketahui',
            'asthma' => 'nullable|in:ya,tidak,diketahui',
            'heart_disease' => 'nullable|in:ya,tidak,diketahui',
            'kidney_disease' => 'nullable|in:ya,tidak,diketahui',
            'hepatitis' => 'nullable|in:ya,tidak,diketahui',
            'tb' => 'nullable|in:ya,tidak,diketahui',
            'allergies' => 'nullable|in:ya,tidak',
            'allergy_details' => 'nullable|string|max:1000',
            'other_conditions' => 'nullable|string|max:1000',
            // Lifestyle
            'smoking' => 'nullable|in:ya,tidak,pernah',
            'alcohol' => 'nullable|in:ya,tidak',
            'exercise' => 'nullable|string|max:100',
            'sleep_pattern' => 'nullable|string|max:100',
            'diet_pattern' => 'nullable|string|max:100',
        ]);

        $history = GtkMedicalHistory::firstOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return back()->with('success', 'Riwayat medis GTK berhasil disimpan.');
    }

    /**
     * Store a vaccination record for a GTK member.
     */
    public function storeVaccination(Request $request, string $gtkUuid)
    {
        $user = User::findOrFail($gtkUuid);

        $validated = $request->validate([
            'vaccine_name' => 'required|string|max:100',
            'given_at' => 'required|date',
            'batch_number' => 'nullable|string|max:100',
            'next_due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['user_id'] = $user->id;
        $validated['administered_by'] = auth()->id();

        GtkVaccination::create($validated);

        return back()->with('success', 'Data vaksinasi berhasil disimpan.');
    }

    /**
     * Destroy a vaccination record.
     */
    public function destroyVaccination(string $vaccinationId)
    {
        GtkVaccination::findOrFail($vaccinationId)->delete();

        return back()->with('success', 'Data vaksinasi berhasil dihapus.');
    }
}
