{{-- Modul Bank Soal & Asesmen — $showDataNilai (default true) --}}
@php $showDataNilai = $showDataNilai ?? true; @endphp
@php $uid = $uid ?? '_'.substr(md5(uniqid('', true)), 0, 6); @endphp
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asesmen.', 'user.kisi-kisi-soal.', 'user.bank-soal.', 'user.soal.', 'user.paket-soal.', 'user.tu-paket-soal.', 'user.bank-soal-terpusat.', 'user.review-soal.', 'user.ljk.']) ? ' active' : '' }}"
       href="{{ route('user.asesmen.index', ['userId' => $userId]) }}">
        <i class="ri-file-edit-line"></i><span>Asesmen</span>@include('layouts.sidebar.partials.indicator-badge', ['key' => 'review_pending', 'title' => 'Dokumen menunggu review Anda'])</a>
</li>

@if($showDataNilai)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.nilai.']) ? ' active' : '' }}"
           href="{{ route('user.schools.nilai.index', ['userId' => $userId]) }}">
            <i class="ri-survey-line"></i><span>Data Nilai</span>@include('layouts.sidebar.partials.indicator-badge', ['key' => 'nilai_kelas', 'title' => 'Kelas yang nilainya belum lengkap'])</a>
    </li>
@endif

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kaldik.', 'user.kalender-kegiatan.']) ? ' active' : '' }}"
       href="#sp{{ $uid }}_kaldik" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAny($currentRoute, ['user.kaldik.', 'user.kalender-kegiatan.']) ? 'true' : 'false' }}"
       aria-controls="sp{{ $uid }}_kaldik">
        <i class="ri-calendar-event-line"></i><span>Kalender Akademik</span><span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.kaldik.', 'user.kalender-kegiatan.']) ? ' show' : '' }}"
         id="sp{{ $uid }}_kaldik">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.kaldik.']) ? ' active' : '' }}"
                   href="{{ route('user.kaldik.index', ['userId' => $userId]) }}">Kalender Pendidikan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.kalender-kegiatan.']) ? ' active' : '' }}"
                   href="{{ route('user.kalender-kegiatan.akademik', ['userId' => $userId]) }}">Agenda Kegiatan</a>
            </li>
        </ul>
    </div>
</li>
