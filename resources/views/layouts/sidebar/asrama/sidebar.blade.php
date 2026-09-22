<!-- Asrama Sidebar — Kepala Asrama, Admin Asrama, Asrama, Wali Asrama -->

@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;

$routeInstance = request()->route();
$routeParams = $routeInstance ? $routeInstance->parameters() : [];
$asramaUuid = $routeParams['asramaUuid'] ?? null;
$hasAsramaContext = !empty($asramaUuid);

/*
|--------------------------------------------------------------------------
| View-As Context
|--------------------------------------------------------------------------
*/
if (!$hasAsramaContext) {
    $viewAsDormitoryId = request()->attributes->get('viewAsDormitoryId');

    if ($viewAsDormitoryId) {
        $asramaUuid = $viewAsDormitoryId;
        $hasAsramaContext = true;
    }
}

/*
|--------------------------------------------------------------------------
| Fallback: first active dormitory
|--------------------------------------------------------------------------
*/
if (!$hasAsramaContext) {
    $firstAsrama = \App\Models\Dormitory::where('is_active', true)->first();

    if ($firstAsrama) {
        $asramaUuid = $firstAsrama->id;
        $hasAsramaContext = true;
    }
}

$asramaProfileFallback = route(
    'user.asrama.my-profile',
    ['userId' => $userId]
);

/*
|--------------------------------------------------------------------------
| Helper Active Route
|--------------------------------------------------------------------------
*/
if (! function_exists('isActiveAsr')) {
    function isActiveAsr($routeName, $pattern)
    {
        if (!$routeName) {
            return false;
        }

        return str_starts_with($routeName, $pattern);
    }
}

/*
|--------------------------------------------------------------------------
| GTK Access
|--------------------------------------------------------------------------
*/
$currentUserJob = $currentUser->gtkEmployment?->jabatan;

$isKepalaAsrama = in_array($currentUserJob, [
    'Kepala Sekolah',
    'Kepala Asrama',
    'Kepala Departemen Tahfidz',
    'Kepala Departemen Bahasa',
    'Kepala Departemen Kesiswaan',
]);
@endphp


{{-- ================================================================
     MENU
================================================================= --}}
<li class="menu-title">
    <span>Menu</span>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'root' ? ' active' : '' }}"
       href="{{ route('root') }}">
        <i class="ri-home-6-line"></i>
        <span>Dashboard</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.profile.my' ? ' active' : '' }}"
       href="{{ route('user.profile.my', ['userId' => $userId]) }}">
        <i class="ri-user-line"></i>
        <span>Profile</span>
    </a>
</li>


{{-- ================================================================
     ASRAMA
================================================================= --}}
<li class="menu-title">
    <span>Asrama</span>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        $currentRoute === 'user.asrama.my-profile' ||
        $currentRoute === 'user.asrama.show'
        ? ' active' : '' 
    }}"
       href="{{ route('user.asrama.my-profile', ['userId' => $userId]) }}">
        <i class="ri-hotel-line"></i>
        <span>Profil Asrama</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.dashboard.pengasuh')
        ? ' active' : '' 
    }}"
       href="{{ route('user.dashboard.pengasuh', ['userId' => $userId]) }}">
        <i class="ri-dashboard-3-line"></i>
        <span>Dashboard Pengasuh</span>
    </a>
</li>


{{-- ================================================================
     OPERASIONAL
================================================================= --}}
<li class="menu-title">
    <span>Operasional</span>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.asrama.residents.')
        ? ' active' : '' 
    }}"
       href="{{ $hasAsramaContext
            ? route('user.asrama.residents.index', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid
            ])
            : $asramaProfileFallback }}">
        <i class="ri-user-follow-line"></i>
        <span>Penghuni</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.asrama.attendance.')
        ? ' active' : '' 
    }}"
       href="{{ $hasAsramaContext
            ? route('user.asrama.attendance.index', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid
            ])
            : $asramaProfileFallback }}">
        <i class="ri-calendar-check-line"></i>
        <span>Absensi</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.asrama.permits.')
        ? ' active' : '' 
    }}"
       href="{{ $hasAsramaContext
            ? route('user.asrama.permits.index', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid
            ])
            : $asramaProfileFallback }}">
        <i class="ri-pass-valid-line"></i>
        <span>Perizinan</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.asrama.dormitory-returns.')
        ? ' active' : '' 
    }}"
       href="{{ $hasAsramaContext
            ? route('user.asrama.dormitory-returns.index', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid
            ])
            : $asramaProfileFallback }}">
        <i class="ri-login-box-line"></i>
        <span>Kedatangan Santri</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.asrama.visits.')
        ? ' active' : '' 
    }}"
       href="{{ $hasAsramaContext
            ? route('user.asrama.visits.index', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid
            ])
            : $asramaProfileFallback }}">
        <i class="ri-footprint-line"></i>
        <span>Kunjungan</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.asrama.room-moves.')
        ? ' active' : '' 
    }}"
       href="{{ $hasAsramaContext
            ? route('user.asrama.room-moves.index', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid
            ])
            : $asramaProfileFallback }}">
        <i class="ri-arrow-left-right-line"></i>
        <span>Mutasi Kamar</span>
    </a>
</li>


{{-- ================================================================
     PEMBINAAN
================================================================= --}}
<li class="menu-title">
    <span>Pembinaan</span>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.asrama.violations.')
        ? ' active' : '' 
    }}"
       href="{{ $hasAsramaContext
            ? route('user.asrama.violations.index', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid
            ])
            : $asramaProfileFallback }}">
        <i class="ri-error-warning-line"></i>
        <span>Pelanggaran</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.asrama.rewards.')
        ? ' active' : '' 
    }}"
       href="{{ $hasAsramaContext
            ? route('user.asrama.rewards.index', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid
            ])
            : $asramaProfileFallback }}">
        <i class="ri-medal-line"></i>
        <span>Penghargaan</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.asrama.room-supervisors.')
        ? ' active' : '' 
    }}"
       href="{{ $hasAsramaContext
            ? route('user.asrama.room-supervisors.index', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid
            ])
            : $asramaProfileFallback }}">
        <i class="ri-shield-user-line"></i>
        <span>Wali Kamar</span>
    </a>
</li>


{{-- ================================================================
     SARANA & PRASARANA
================================================================= --}}
<li class="menu-title">
    <span>Sarana & Prasarana</span>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.asrama.wings.')
        ? ' active' : '' 
    }}"
       href="{{ $hasAsramaContext
            ? route('user.asrama.wings.index', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid
            ])
            : $asramaProfileFallback }}">
        <i class="ri-stack-line"></i>
        <span>Gedung</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.asrama.rooms.')
        ? ' active' : '' 
    }}"
       href="{{ $hasAsramaContext
            ? route('user.asrama.rooms.index', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid
            ])
            : $asramaProfileFallback }}">
        <i class="ri-door-open-line"></i>
        <span>Kamar</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.asrama.inventories.')
        ? ' active' : '' 
    }}"
       href="{{ $hasAsramaContext
            ? route('user.asrama.inventories.index', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid
            ])
            : $asramaProfileFallback }}">
        <i class="ri-archive-line"></i>
        <span>Inventaris</span>
    </a>
</li>


{{-- ================================================================
     KONFIGURASI
================================================================= --}}
<li class="menu-title">
    <span>Konfigurasi</span>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.asrama.leave-policies')
        ? ' active' : '' 
    }}"
       href="{{ $hasAsramaContext
            ? route('user.asrama.leave-policies.index', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid
            ])
            : $asramaProfileFallback }}">
        <i class="ri-settings-4-line"></i>
        <span>Konfigurasi Izin</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.boarding-policies.')
        ? ' active' : '' 
    }}"
       href="{{ route('user.boarding-policies.index', ['userId' => $userId]) }}">
        <i class="ri-file-shield-2-line"></i>
        <span>Kebijakan Asrama</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.calendar.return.')
        ? ' active' : '' 
    }}"
       href="{{ route('user.calendar.return.index', ['userId' => $userId]) }}">
        <i class="ri-calendar-event-line"></i>
        <span>Kalender Kepulangan</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.calendar.visit.')
        ? ' active' : '' 
    }}"
       href="{{ route('user.calendar.visit.index', ['userId' => $userId]) }}">
        <i class="ri-footprint-line"></i>
        <span>Kalender Kunjungan</span>
    </a>
</li>


{{-- ================================================================
     LAPORAN
================================================================= --}}
<li class="menu-title">
    <span>Laporan</span>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.asrama.reports.')
        ? ' active' : '' 
    }}"
       href="{{ $hasAsramaContext
            ? route('user.asrama.reports.index', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid
            ])
            : $asramaProfileFallback }}">
        <i class="ri-file-text-line"></i>
        <span>Laporan</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.asrama.approval-center')
        ? ' active' : '' 
    }}"
       href="{{ $hasAsramaContext
            ? route('user.asrama.approval-center', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid
            ])
            : $asramaProfileFallback }}">
        <i class="ri-inbox-line"></i>
        <span>Approval Center</span>
    </a>
</li>


{{-- ================================================================
     INFORMASI & KEGIATAN
================================================================= --}}
<li class="menu-title">
    <span>Informasi & Kegiatan</span>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.asrama.posts.')
        ? ' active' : '' 
    }}"
       href="{{ $hasAsramaContext
            ? route('user.asrama.posts.index', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid
            ])
            : $asramaProfileFallback }}">
        <i class="ri-megaphone-line"></i>
        <span>Informasi</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.asrama.broadcasts.')
        ? ' active' : '' 
    }}"
       href="{{ $hasAsramaContext
            ? route('user.asrama.broadcasts.index', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid
            ])
            : $asramaProfileFallback }}">
        <i class="ri-notification-3-line"></i>
        <span>Broadcast</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.asrama.activities.')
        ? ' active' : '' 
    }}"
       href="{{ $hasAsramaContext
            ? route('user.asrama.activities.index', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid
            ])
            : $asramaProfileFallback }}">
        <i class="ri-file-list-3-line"></i>
        <span>Log Kegiatan</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.asrama.templates.')
        ? ' active' : '' 
    }}"
       href="{{ $hasAsramaContext
            ? route('user.asrama.templates.index', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid
            ])
            : $asramaProfileFallback }}">
        <i class="ri-git-branch-line"></i>
        <span>Template Kegiatan</span>
    </a>
</li>


{{-- ================================================================
     PESERTA DIDIK
================================================================= --}}
<li class="menu-title">
    <span>Peserta Didik</span>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.students.') &&
        !str_contains($currentRoute, 'mahrom')
        ? ' active' : '' 
    }}"
       href="{{ route('user.students.index', ['userId' => $userId]) }}">
        <i class="ri-user-star-line"></i>
        <span>Data Santri</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        str_contains($currentRoute, 'mahrom')
        ? ' active' : '' 
    }}"
       href="{{ route('user.students.mahroms.global', ['userId' => $userId]) }}">
        <i class="ri-parent-line"></i>
        <span>Data Mahrom</span>
    </a>
</li>


{{-- ================================================================
     GTK — Kepala Asrama & Pejabat Terkait
================================================================= --}}
@if($isKepalaAsrama)

<li class="menu-title">
    <span>GTK</span>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.gtk-additional-tasks.')
        ? ' active' : '' 
    }}"
       href="{{ route('user.gtk-additional-tasks.index', ['userId' => $userId]) }}">
        <i class="ri-task-line"></i>
        <span>Tugas Tambahan GTK</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.gtk-positions.')
        ? ' active' : '' 
    }}"
       href="{{ route('user.gtk-positions.index', ['userId' => $userId]) }}">
        <i class="ri-briefcase-line"></i>
        <span>Jabatan GTK</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ 
        isActiveAsr($currentRoute, 'user.gtk-position-proposals.')
        ? ' active' : '' 
    }}"
       href="{{ route('user.gtk-position-proposals.index', ['userId' => $userId]) }}">
        <i class="ri-arrow-up-line"></i>
        <span>Pengajuan Jabatan</span>
    </a>
</li>

@endif