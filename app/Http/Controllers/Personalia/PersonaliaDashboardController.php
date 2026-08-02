<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use App\Models\GtkEmployment;
use App\Models\GtkProfile;
use App\Models\PensionSetting;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersonaliaDashboardController extends Controller
{
    public function dashboard(Request $request, string $userId)
    {
        // ── 1. GTK Overview Stats ────────────────────────────────
        $stats = $this->getGtkStats();

        // ── 2. GTK by Work Unit ───────────────────────────────────
        $gtkByWorkUnit = $this->getGtkByWorkUnit();

        // ── 3. GTK by Jenis ───────────────────────────────────────
        $gtkByJenis = $this->getGtkByJenis();

        // ── 4. GTK Approach BUP (Pensiun) ─────────────────────────
        $approachingBup = $this->getApproachingBup();

        // ── 5. Kontrak Will Expire ────────────────────────────────
        $expiringContracts = $this->getExpiringContracts();

        // ── 6. GTK by Gender ──────────────────────────────────────
        $gtkByGender = $this->getGtkByGender();

        // ── 7. Recent GTK (newly added) ──────────────────────────
        $recentGtk = $this->getRecentGtk();

        // ── 8. GTK Without Kontrak ───────────────────────────────
        $gtkWithoutKontrak = $this->getGtkWithoutKontrak();

        // ── 9. Attendance Summary (mock for now) ──────────────────
        $attendanceSummary = $this->getAttendanceSummary();

        // ── 10. Quick Stats ───────────────────────────────────────
        $quickStats = $this->getQuickStats();

        return view('personalia.dashboard', compact(
            'userId',
            'stats',
            'gtkByWorkUnit',
            'gtkByJenis',
            'approachingBup',
            'expiringContracts',
            'gtkByGender',
            'recentGtk',
            'gtkWithoutKontrak',
            'attendanceSummary',
            'quickStats',
        ));
    }

    private function getGtkStats()
    {
        $total = User::where('is_active', true)
            ->whereHas('employment')
            ->count();

        $guru = User::where('is_active', true)
            ->whereHas('employment', fn ($q) => $q->where('jenis_gtk', 'Guru'))
            ->count();

        $tendik = User::where('is_active', true)
            ->whereHas('employment', fn ($q) => $q->where('jenis_gtk', 'Tendik'))
            ->count();

        $male = User::where('is_active', true)
            ->whereHas('employment')
            ->whereHas('gtkProfile', fn ($q) => $q->where('jenis_kelamin', 'Laki-laki'))
            ->count();

        $female = User::where('is_active', true)
            ->whereHas('employment')
            ->whereHas('gtkProfile', fn ($q) => $q->where('jenis_kelamin', 'Perempuan'))
            ->count();

        return [
            'total' => $total,
            'guru' => $guru,
            'tendik' => $tendik,
            'male' => $male,
            'female' => $female,
        ];
    }

    private function getGtkByWorkUnit()
    {
        return DB::table('gtk_work_unit')
            ->join('work_units', 'gtk_work_unit.work_unit_id', '=', 'work_units.id')
            ->join('users', 'gtk_work_unit.user_id', '=', 'users.id')
            ->where('users.is_active', true)
            ->select('work_units.name', DB::raw('COUNT(*) as total'))
            ->groupBy('work_units.id', 'work_units.name')
            ->orderBy('total', 'desc')
            ->get();
    }

    private function getGtkByJenis()
    {
        return GtkEmployment::whereNotNull('jenis_gtk')
            ->select('jenis_gtk as name', DB::raw('COUNT(*) as total'))
            ->groupBy('jenis_gtk')
            ->get();
    }

    private function getApproachingBup()
    {
        $notifMonths = (int) PensionSetting::get('notification_months', '6');
        $bupAge = (int) PensionSetting::get('bup_age', '58');

        return User::where('users.is_active', true)
            ->join('gtk_profiles', 'users.id', '=', 'gtk_profiles.user_id')
            ->leftJoin('gtk_pensions', function ($join) {
                $join->on('users.id', '=', 'gtk_pensions.user_id')
                    ->whereIn('gtk_pensions.pension_status', ['completed', 'cancelled']);
            })
            ->whereNull('gtk_pensions.id')
            ->whereNotNull('gtk_profiles.tanggal_lahir')
            ->whereRaw('DATE_ADD(gtk_profiles.tanggal_lahir, INTERVAL ? YEAR) <= DATE_ADD(NOW(), INTERVAL ? MONTH)',
                [$bupAge, $notifMonths])
            ->select('users.id', 'users.name', 'gtk_profiles.tanggal_lahir')
            ->orderByRaw('DATE_ADD(gtk_profiles.tanggal_lahir, INTERVAL ? YEAR)', [$bupAge])
            ->limit(10)
            ->get();
    }

    private function getExpiringContracts()
    {
        // For now, return mock data since kontrak table might not exist
        return collect([]);
    }

    private function getGtkByGender()
    {
        $male = GtkProfile::where('jenis_kelamin', 'Laki-laki')->count();
        $female = GtkProfile::where('jenis_kelamin', 'Perempuan')->count();

        return [
            'male' => $male,
            'female' => $female,
            'total' => $male + $female,
        ];
    }

    private function getRecentGtk()
    {
        return User::where('is_active', true)
            ->whereHas('employment')
            ->with('employment')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'name', 'email', 'created_at']);
    }

    private function getGtkWithoutKontrak()
    {
        // Count GTK without active kontrsk - placeholder
        return 0;
    }

    private function getAttendanceSummary()
    {
        // Placeholder for attendance data
        return [
            'present' => 0,
            'absent' => 0,
            'permission' => 0,
            'late' => 0,
        ];
    }

    private function getQuickStats()
    {
        $workUnits = WorkUnit::where('is_active', true)->count();
        $schools = DB::table('schools')->where('is_active', true)->count();

        return [
            'work_units' => $workUnits,
            'schools' => $schools,
        ];
    }
}
