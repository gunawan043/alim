<!-- Personalia Sidebar -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;

function isActiveP($routeName, $pattern) {
    if (!$routeName) return false;
    return str_starts_with($routeName, $pattern);
}

// Count GTK approaching BUP for pension badge
$p_notif_months = (int) (\App\Models\PensionSetting::get('notification_months', '6'));
$p_bup_age = (int) (\App\Models\PensionSetting::get('bup_age', '58'));
$p_approaching = DB::table('users')
    ->join('gtk_profiles', 'users.id', '=', 'gtk_profiles.user_id')
    ->leftJoin('gtk_pensions', function($join) {
        $join->on('users.id', '=', 'gtk_pensions.user_id')
             ->whereIn('gtk_pensions.pension_status', ['completed', 'cancelled']);
    })
    ->where('users.is_active', true)
    ->whereNotNull('gtk_profiles.tanggal_lahir')
    ->whereNull('gtk_pensions.id')
    ->whereRaw("DATE_ADD(gtk_profiles.tanggal_lahir, INTERVAL ? YEAR) <= DATE_ADD(NOW(), INTERVAL ? MONTH)",
        [$p_bup_age, $p_notif_months])
    ->count();
@endphp

<li class="menu-title"><span>Menu</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.dashboard' ? ' active' : '' }}"
       href="{{ route('user.dashboard', ['userId' => $userId]) }}">
        <i class="ri-home-6-line"></i>
        <span>Dashboard</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.todos.index' ? ' active' : '' }}"
       href="{{ route('user.todos.index', ['userId' => $userId]) }}">
        <i class="ri-task-line"></i>
        <span>Todo List</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.profile.my' ? ' active' : '' }}"
       href="{{ route('user.profile.my', ['userId' => $userId]) }}">
        <i class="ri-user-line"></i>
        <span>Profile</span>
    </a>
</li>

<li class="menu-title"><span>GTK</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.gtk.') ? ' active' : '' }}"
       href="#data_gtk" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveP($currentRoute, 'user.gtk.') ? 'true' : 'false' }}"
       aria-controls="data_gtk">
        <i class="ri-contacts-book-2-line"></i>
        <span>Data GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveP($currentRoute, 'user.gtk.') ? ' show' : '' }}" id="data_gtk">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.gtk.index' ? ' active' : '' }}"
                   href="{{ route('user.gtk.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Semua GTK</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.gtk.indexguru' ? ' active' : '' }}"
                   href="{{ route('user.gtk.indexguru', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Guru</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.gtk.indextendik' ? ' active' : '' }}"
                   href="{{ route('user.gtk.indextendik', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Tendik</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.gtk.import' ? ' active' : '' }}"
                   href="{{ route('user.gtk.import', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">
                    <i class="ri-file-upload-line"></i>
                    <span>Import / Export</span>
                </a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.gtk-requests.') ? ' active' : '' }}"
       href="#gtk_requests" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveP($currentRoute, 'user.gtk-requests.') ? 'true' : 'false' }}"
       aria-controls="gtk_requests">
        <i class="ri-file-add-line"></i>
        <span>Pengajuan GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveP($currentRoute, 'user.gtk-requests.') ? ' show' : '' }}" id="gtk_requests">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.gtk-requests.index' ? ' active' : '' }}"
                   href="{{ route('user.gtk-requests.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Daftar Pengajuan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.gtk-requests.create' ? ' active' : '' }}"
                   href="{{ route('user.gtk-requests.create', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Buat Pengajuan</a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.approvals.') ? ' active' : '' }}"
       href="#approvals" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveP($currentRoute, 'user.approvals.') ? 'true' : 'false' }}"
       aria-controls="approvals">
        <i class="ri-git-pull-request-line"></i>
        <span>Approval</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveP($currentRoute, 'user.approvals.') ? ' show' : '' }}" id="approvals">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.approvals.index' ? ' active' : '' }}"
                   href="{{ route('user.approvals.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Daftar Approval</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.approvals.my-pending' ? ' active' : '' }}"
                   href="{{ route('user.approvals.my-pending', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Menunggu Saya</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.approvals.history' ? ' active' : '' }}"
                   href="{{ route('user.approvals.history', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Riwayat</a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.ats.') ? ' active' : '' }}"
       href="#ats_recruitment" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveP($currentRoute, 'user.ats.') ? 'true' : 'false' }}"
       aria-controls="ats_recruitment">
        <i class="ri-user-add-line"></i>
        <span>Rekrutmen</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveP($currentRoute, 'user.ats.') ? ' show' : '' }}" id="ats_recruitment">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.ats.jobs.index' ? ' active' : '' }}"
                   href="{{ route('user.ats.jobs.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Lowongan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.ats.candidates.index' ? ' active' : '' }}"
                   href="{{ route('user.ats.candidates.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Kandidat</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.ats.applications.index' ? ' active' : '' }}"
                   href="{{ route('user.ats.applications.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Lamaran</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.ats.interviews.index' ? ' active' : '' }}"
                   href="{{ route('user.ats.interviews.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Interview</a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.jenjang-karir.') ? ' active' : '' }}"
       href="#jenjang_karir" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveP($currentRoute, 'user.jenjang-karir.') ? 'true' : 'false' }}"
       aria-controls="jenjang_karir">
        <i class="ri-rocket-line"></i>
        <span>Jenjang Karir</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveP($currentRoute, 'user.jenjang-karir.') ? ' show' : '' }}" id="jenjang_karir">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.jenjang-karir.career-path.index' ? ' active' : '' }}"
                   href="{{ route('user.jenjang-karir.career-path.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Career Path</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.jenjang-karir.mutasi.index' ? ' active' : '' }}"
                   href="{{ route('user.jenjang-karir.mutasi.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Mutasi & Rotasi</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.jenjang-karir.promosi.index' ? ' active' : '' }}"
                   href="{{ route('user.jenjang-karir.promosi.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Promosi & Demosi</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.jenjang-karir.talent.index' ? ' active' : '' }}"
                   href="{{ route('user.jenjang-karir.talent.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Talent Pool</a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.pension.index' ? ' active' : '' }}"
       href="{{ route('user.pension.index', ['userId' => $userId]) }}">
        <i class="ri-umbrella-line"></i>
        <span>Pensiun</span>
        @if($p_approaching > 0)
        <span class="badge bg-danger rounded-pill" style="font-size:0.65rem; padding: 0.2em 0.5em;">{{ $p_approaching }}</span>
        @endif
    </a>
</li>

<li class="menu-title"><span>HRD</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.absensi-gtk.') ? ' active' : '' }}"
       href="#absensi_gtk" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveP($currentRoute, 'user.absensi-gtk.') ? 'true' : 'false' }}"
       aria-controls="absensi_gtk">
        <i class="ri-time-line"></i>
        <span>Absensi GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveP($currentRoute, 'user.absensi-gtk.') ? ' show' : '' }}" id="absensi_gtk">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.absensi-gtk.index' ? ' active' : '' }}"
                   href="{{ route('user.absensi-gtk.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Rekap Absensi</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.absensi-gtk.harian' ? ' active' : '' }}"
                   href="{{ route('user.absensi-gtk.harian', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Absensi Harian</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.absensi-gtk.rekap-bulanan' ? ' active' : '' }}"
                   href="{{ route('user.absensi-gtk.rekap-bulanan', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Rekap Bulanan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.absensi-gtk.izin' ? ' active' : '' }}"
                   href="{{ route('user.absensi-gtk.izin', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Pengajuan Izin</a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.cuti.') ? ' active' : '' }}"
       href="#cuti_izin" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveP($currentRoute, 'user.cuti.') ? 'true' : 'false' }}"
       aria-controls="cuti_izin">
        <i class="ri-calendar-check-line"></i>
        <span>Cuti & Izin</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveP($currentRoute, 'user.cuti.') ? ' show' : '' }}" id="cuti_izin">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.cuti.index' ? ' active' : '' }}"
                   href="{{ route('user.cuti.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Daftar Cuti</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.cuti.create' ? ' active' : '' }}"
                   href="{{ route('user.cuti.create', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Ajukan Cuti</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.cuti.approval' ? ' active' : '' }}"
                   href="{{ route('user.cuti.approval', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Persetujuan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.cuti.rekap' ? ' active' : '' }}"
                   href="{{ route('user.cuti.rekap', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Rekap Cuti</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.cuti.quota' ? ' active' : '' }}"
                   href="{{ route('user.cuti.quota', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Kuota GTK</a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.payroll.') || isActiveP($currentRoute, 'user.payroll-slip.') ? ' active' : '' }}"
       href="#payroll" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveP($currentRoute, 'user.payroll.') || isActiveP($currentRoute, 'user.payroll-slip.') ? 'true' : 'false' }}"
       aria-controls="payroll">
        <i class="ri-wallet-line"></i>
        <span>Payroll & Gaji</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveP($currentRoute, 'user.payroll.') || isActiveP($currentRoute, 'user.payroll-slip.') ? ' show' : '' }}" id="payroll">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.payroll.index' ? ' active' : '' }}"
                   href="{{ route('user.payroll.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Daftar Gaji</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.payroll-slip.index' ? ' active' : '' }}"
                   href="{{ route('user.payroll-slip.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Slip Gaji</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.payroll.tunjangan' ? ' active' : '' }}"
                   href="{{ route('user.payroll.tunjangan', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Tunjangan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.payroll.potongan' ? ' active' : '' }}"
                   href="{{ route('user.payroll.potongan', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Potongan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.payroll.bpjstk' ? ' active' : '' }}"
                   href="{{ route('user.payroll.bpjstk', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">BPJS TK</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.payroll.bpjs-kes' ? ' active' : '' }}"
                   href="{{ route('user.payroll.bpjs-kes', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">BPJS Kesehatan</a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.kontrak.') ? ' active' : '' }}"
       href="#kontrak_kerja" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveP($currentRoute, 'user.kontrak.') ? 'true' : 'false' }}"
       aria-controls="kontrak_kerja">
        <i class="ri-file-paper-line"></i>
        <span>Kontrak Kerja</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveP($currentRoute, 'user.kontrak.') ? ' show' : '' }}" id="kontrak_kerja">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.kontrak.index' ? ' active' : '' }}"
                   href="{{ route('user.kontrak.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Daftar Kontrak</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.kontrak.create' ? ' active' : '' }}"
                   href="{{ route('user.kontrak.create', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Buat Kontrak</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.kontrak.expiring' ? ' active' : '' }}"
                   href="{{ route('user.kontrak.expiring', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Akan Berakhir</a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.kinerja.') ? ' active' : '' }}"
       href="#penilaian_kinerja" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveP($currentRoute, 'user.kinerja.') ? 'true' : 'false' }}"
       aria-controls="penilaian_kinerja">
        <i class="ri-bar-chart-box-line"></i>
        <span>Kinerja</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveP($currentRoute, 'user.kinerja.') ? ' show' : '' }}" id="penilaian_kinerja">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.kinerja.index' ? ' active' : '' }}"
                   href="{{ route('user.kinerja.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Daftar Penilaian</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.kinerja.indikator' ? ' active' : '' }}"
                   href="{{ route('user.kinerja.indikator', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Indikator</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.kinerja.reward' ? ' active' : '' }}"
                   href="{{ route('user.kinerja.reward', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Reward & Punishment</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.kinerja.laporan' ? ' active' : '' }}"
                   href="{{ route('user.kinerja.laporan', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Laporan</a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.pelatihan.') ? ' active' : '' }}"
       href="#pelatihan" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveP($currentRoute, 'user.pelatihan.') ? 'true' : 'false' }}"
       aria-controls="pelatihan">
        <i class="ri-graduation-cap-line"></i>
        <span>Pelatihan</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveP($currentRoute, 'user.pelatihan.') ? ' show' : '' }}" id="pelatihan">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.pelatihan.index' ? ' active' : '' }}"
                   href="{{ route('user.pelatihan.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Daftar Pelatihan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.pelatihan.peserta' ? ' active' : '' }}"
                   href="{{ route('user.pelatihan.peserta', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Peserta</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.pelatihan.sertifikasi' ? ' active' : '' }}"
                   href="{{ route('user.pelatihan.sertifikasi', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Sertifikasi</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.pelatihan.rekap' ? ' active' : '' }}"
                   href="{{ route('user.pelatihan.rekap', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Rekap</a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.kesejahteraan.') ? ' active' : '' }}"
       href="{{ route('user.kesejahteraan.index', ['userId' => $userId]) }}">
        <i class="ri-heart-line"></i>
        <span>Kesejahteraan</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.peraturan.') ? ' active' : '' }}"
       href="{{ route('user.peraturan.index', ['userId' => $userId]) }}">
        <i class="ri-shield-check-line"></i>
        <span>Peraturan</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.jam-kerja.') ? ' active' : '' }}"
       href="{{ route('user.jam-kerja.index', ['userId' => $userId]) }}">
        <i class="ri-time-sensor-line"></i>
        <span>Jam Kerja</span>
    </a>
</li>

<li class="menu-title"><span>Referensi</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.master-data.') ? ' active' : '' }}"
       href="#master_data" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveP($currentRoute, 'user.master-data.') ? 'true' : 'false' }}"
       aria-controls="master_data">
        <i class="ri-database-2-line"></i>
        <span>Master Data</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveP($currentRoute, 'user.master-data.') ? ' show' : '' }}" id="master_data">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.master-data.jenis-gtk.index' ? ' active' : '' }}"
                   href="{{ route('user.master-data.jenis-gtk.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Jenis GTK</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.master-data.jabatan.index' ? ' active' : '' }}"
                   href="{{ route('user.master-data.jabatan.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Jabatan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.master-data.satuan-kerja.index' ? ' active' : '' }}"
                   href="{{ route('user.master-data.satuan-kerja.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Satuan Kerja</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.master-data.mata-pelajaran.index' ? ' active' : '' }}"
                   href="{{ route('user.master-data.mata-pelajaran.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Mata Pelajaran</a>
            </li>
        </ul>
    </div>
</li>

{{-- LAPORAN --}}
<li class="menu-title"><span>Laporan</span></li>

{{-- KEHADIRAN --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.kehadiran.') ? ' active' : '' }}"
       href="#kehadiran" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveP($currentRoute, 'user.kehadiran.') ? 'true' : 'false' }}"
       aria-controls="kehadiran">
        <i class="mdi mdi-badge-account-horizontal-outline"></i>
        <span>Kehadiran</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveP($currentRoute, 'user.kehadiran.') ? ' show' : '' }}" id="kehadiran">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.kehadiran.pergantian-jam' ? ' active' : '' }}"
                   href="{{ route('user.kehadiran.pergantian-jam', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Pergantian Jam</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.kehadiran.rekap' ? ' active' : '' }}"
                   href="{{ route('user.kehadiran.rekap', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Rekap Kehadiran</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.kehadiran.cuti-izin' ? ' active' : '' }}"
                   href="{{ route('user.kehadiran.cuti-izin', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Cuti & Izin</a>
            </li>
        </ul>
    </div>
</li>

{{-- RAPOR GTK --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.rapor-gtk.') ? ' active' : '' }}"
       href="#rapor_gtk" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveP($currentRoute, 'user.rapor-gtk.') ? 'true' : 'false' }}"
       aria-controls="rapor_gtk">
        <i class="ri-newspaper-line"></i>
        <span>Rapor GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveP($currentRoute, 'user.rapor-gtk.') ? ' show' : '' }}" id="rapor_gtk">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.rapor-gtk.akademik' ? ' active' : '' }}"
                   href="{{ route('user.rapor-gtk.akademik', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Penilaian Akademik</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.rapor-gtk.disiplin' ? ' active' : '' }}"
                   href="{{ route('user.rapor-gtk.disiplin', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Penilaian Disiplin</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.rapor-gtk.kepribadian' ? ' active' : '' }}"
                   href="{{ route('user.rapor-gtk.kepribadian', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Penilaian Kepribadian</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.rapor-gtk.administrasi' ? ' active' : '' }}"
                   href="{{ route('user.rapor-gtk.administrasi', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Penilaian Administrasi</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.rapor-gtk.tahunan' ? ' active' : '' }}"
                   href="{{ route('user.rapor-gtk.tahunan', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Rekap Tahunan</a>
            </li>
        </ul>
    </div>
</li>

{{-- KALENDER KEGIATAN --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.kalender-kegiatan.') ? ' active' : '' }}"
       href="#kalender_kegiatan" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveP($currentRoute, 'user.kalender-kegiatan.') ? 'true' : 'false' }}"
       aria-controls="kalender_kegiatan">
        <i class="ri-calendar-todo-line"></i>
        <span>Kalender Kegiatan</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveP($currentRoute, 'user.kalender-kegiatan.') ? ' show' : '' }}" id="kalender_kegiatan">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.kalender-kegiatan.akademik' ? ' active' : '' }}"
                   href="{{ route('user.kalender-kegiatan.akademik', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Kalender Akademik</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.kalender-kegiatan.kontrak' ? ' active' : '' }}"
                   href="{{ route('user.kalender-kegiatan.kontrak', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Kalender Kontrak</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.kalender-kegiatan.evaluasi' ? ' active' : '' }}"
                   href="{{ route('user.kalender-kegiatan.evaluasi', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Jadwal Evaluasi GTK</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.kalender-kegiatan.training' ? ' active' : '' }}"
                   href="{{ route('user.kalender-kegiatan.training', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Training & Workshop</a>
            </li>
        </ul>
    </div>
</li>

{{-- ANALISIS GTK --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.analisis-gtk.') ? ' active' : '' }}"
       href="#analisis_gtk" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveP($currentRoute, 'user.analisis-gtk.') ? 'true' : 'false' }}"
       aria-controls="analisis_gtk">
        <i class="ri-pencil-ruler-2-line"></i>
        <span>Analisis GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveP($currentRoute, 'user.analisis-gtk.') ? ' show' : '' }}" id="analisis_gtk">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.analisis-gtk.beban-kerja' ? ' active' : '' }}"
                   href="{{ route('user.analisis-gtk.beban-kerja', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Beban Kerja</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.analisis-gtk.rasio-ideal' ? ' active' : '' }}"
                   href="{{ route('user.analisis-gtk.rasio-ideal', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Rasio Ideal</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.analisis-gtk.proyeksi' ? ' active' : '' }}"
                   href="{{ route('user.analisis-gtk.proyeksi', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Proyeksi SDM</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.analisis-gtk.gap' ? ' active' : '' }}"
                   href="{{ route('user.analisis-gtk.gap', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Gap Analysis</a>
            </li>
        </ul>
    </div>
</li>
