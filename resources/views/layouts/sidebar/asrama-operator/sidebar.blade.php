@php
    $user = auth()->user();
    $operatorAssignment = \App\Models\StudentOperatorAssignment::query()
        ->where('user_id', $user->id)
        ->where('operator_type', 'asrama')
        ->where('is_active', true)
        ->first();
@endphp

<li class="menu-title"><span>Operator Asrama</span></li>
@if($operatorAssignment?->dormitory_id)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.visits.']) ? ' active' : '' }}"
           href="{{ route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $operatorAssignment->dormitory_id]) }}">
            <i class="ri-footprint-line"></i><span>Kunjungan &amp; Check-in</span>
        </a>
    </li>
@else
    <li class="nav-item">
        <div class="alert alert-warning m-2 small">Belum ada penugasan asrama aktif. Hubungi kepala/wakil asrama.</div>
    </li>
@endif
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.profile.']) ? ' active' : '' }}"
       href="{{ route('user.profile.my', ['userId' => $userId]) }}">
        <i class="ri-user-line"></i><span>Profil Saya</span>
    </a>
</li>
