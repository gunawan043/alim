{{-- Prestasi Santri (dipakai Wali Kelas & Kesiswaan) --}}
@php $uid = $uid ?? '_'.substr(md5(uniqid('', true)), 0, 6); @endphp
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.student-achievements.', 'user.student-achievement.']) ? ' active' : '' }}"
       href="#sp{{ $uid }}_prestasi" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAny($currentRoute, ['user.student-achievements.', 'user.student-achievement.']) ? 'true' : 'false' }}"
       aria-controls="sp{{ $uid }}_prestasi">
        <i class="ri-trophy-line"></i><span>Prestasi Santri</span>
        @if($mumtazPendingCount > 0)
            <span class="badge bg-danger rounded-pill ms-1">{{ $mumtazPendingCount }}</span>
        @endif
        <span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.student-achievements.', 'user.student-achievement.']) ? ' show' : '' }}"
         id="sp{{ $uid }}_prestasi">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ request('type') !== 'quran' && request('type') !== 'hadits' ? ' active' : '' }}"
                   href="{{ route('user.student-achievement.index', ['userId' => $userId, 'type' => 'akademik']) }}">
                    Prestasi Akademik
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ request('type') === 'quran' ? ' active' : '' }}"
                   href="{{ route('user.student-achievement.index', ['userId' => $userId, 'type' => 'quran']) }}">
                    Hafalan Qur'an
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ request('type') === 'hadits' ? ' active' : '' }}"
                   href="{{ route('user.student-achievement.index', ['userId' => $userId, 'type' => 'hadits']) }}">
                    Hafalan Hadits
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.student-achievement.candidates.']) ? ' active' : '' }}"
                   href="{{ route('user.student-achievement.candidates.index', ['userId' => $userId]) }}">
                    <i class="ri-award-line me-1"></i>Kandidat Mumtaz Murtafi'
                    @if($mumtazPendingCount > 0)
                        <span class="badge bg-danger rounded-pill ms-1">{{ $mumtazPendingCount }}</span>
                    @endif
                </a>
            </li>
        </ul>
    </div>
</li>
