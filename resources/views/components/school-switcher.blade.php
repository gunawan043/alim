{{-- resources/views/components/school-switcher.blade.php --}}
{{--
  School Switcher — untuk Super Admin sidebar.
  Menggunakan session-based school context.
  Scope hanya affect data (GTK, Santri, Akademik, dll), bukan menu.

  Props (passed via @include):
    - schools: Collection of School models
    - saSchoolId: string|null   — UUID sekolah yang sedang aktif di session
    - saSchoolName: string|null — nama sekolah
    - saSchoolScoped: bool      — apakah sedang scoped
--}}
@php
$allSchools = $schools instanceof \Illuminate\Support\Collection ? $schools : collect();

// Group by level
$grouped = $allSchools->groupBy('school_level')->map(function ($group, $level) {
    $label = match ($level) {
        'sd' => 'SD IT',
        'smp' => 'SMP IT',
        'sma' => 'SMA IT / MA',
        'smk' => 'SMA IT / MA',
        default => strtoupper($level),
    };
    return [
        'level' => $level,
        'label' => $label,
        'schools' => $group->values(),
    ];
})->values();

// From SidebarComposer session vars
$currentSchoolId = $saSchoolId ?? session('sa_school_id');
$currentSchoolName = $saSchoolName ?? session('sa_school_name');
$isScoped = $saSchoolScoped ?? session('sa_school_scoped', false);

$currentUrl = url()->current();
@endphp

{{-- School Context Switcher Card --}}
<div class="school-switcher mb-2 px-2">
    {{-- Label --}}
    <div class="d-flex align-items-center gap-1 mb-1" style="font-size:0.7rem; color: #878a99; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">
        <i class="ri-government-line" style="font-size:0.8rem;"></i>
        <span>Satuan Pendidikan</span>
        @if($isScoped && $currentSchoolName)
            <span class="badge bg-success ms-auto" style="font-size:0.6rem; padding: 1px 5px;">Scoped</span>
        @endif
    </div>

    {{-- Dropdown Button --}}
    <div class="dropdown w-100">
        <button class="btn btn-light btn-sm w-100 text-start d-flex align-items-center justify-content-between school-switcher-btn"
                type="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                style="font-size:0.8rem; border: 1px solid #e9ecef; background: {{ $isScoped ? '#f0f4ff' : 'white' }};">
            <span class="d-flex align-items-center gap-2 overflow-hidden">
                @if($isScoped && $currentSchoolName)
                    <i class="ri-checkbox-circle-fill text-success flex-shrink-0"></i>
                    <span class="text-truncate">{{ $currentSchoolName }}</span>
                @else
                    <i class="ri-earth-line text-muted flex-shrink-0"></i>
                    <span class="text-muted text-truncate">Semua Sekolah</span>
                @endif
            </span>
            <i class="ri-arrow-down-s-line text-muted flex-shrink-0" style="font-size:1rem;"></i>
        </button>

        {{-- Dropdown Menu --}}
        <ul class="dropdown-menu w-100 shadow-sm school-switcher-menu"
            style="max-height: 320px; overflow-y: auto; font-size:0.8rem;">

            {{-- Semua Sekolah option --}}
            <li>
                <form method="POST" action="{{ route('school-switch') }}" class="school-switch-form">
                    @csrf
                    <input type="hidden" name="school_id" value="all">
                    <input type="hidden" name="redirect_to" value="{{ $currentUrl }}">
                    <button type="submit"
                            class="dropdown-item d-flex align-items-center gap-2 {{ !$isScoped ? 'active' : '' }}"
                            style="{{ !$isScoped ? 'background:#f0f4ff; font-weight:600;' : '' }}">
                        <i class="ri-earth-line text-primary"></i>
                        Semua Sekolah
                        @if(!$isScoped)
                            <i class="ri-check-line text-primary ms-auto"></i>
                        @endif
                    </button>
                </form>
            </li>

            <li><hr class="dropdown-divider my-1"></li>

            {{-- Schools grouped by level --}}
            @foreach($grouped as $group)
                <li class="school-group-header">
                    <span class="dropdown-header px-3 py-1" style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#878a99;">
                        {{ $group['label'] }}
                    </span>
                </li>
                @foreach($group['schools'] as $school)
                    <li>
                        <form method="POST" action="{{ route('school-switch') }}" class="school-switch-form">
                            @csrf
                            <input type="hidden" name="school_id" value="{{ $school->id }}">
                            <input type="hidden" name="redirect_to" value="{{ $currentUrl }}">
                            <button type="submit"
                                    class="dropdown-item d-flex align-items-center gap-2 px-3 {{ $isScoped && $currentSchoolId === $school->id ? 'active' : '' }}"
                                    style="{{ $isScoped && $currentSchoolId === $school->id ? 'background:#f0f4ff; font-weight:600;' : '' }}">
                                <i class="{{ $school->school_gender === 'putri' ? 'ri-women-line text-danger' : 'ri-men-line text-primary' }}"></i>
                                <span class="text-truncate flex-grow-1">{{ $school->name }}</span>
                                @if($isScoped && $currentSchoolId === $school->id)
                                    <i class="ri-check-line text-success ms-auto"></i>
                                @endif
                            </button>
                        </form>
                    </li>
                @endforeach
            @endforeach

            @if($allSchools->isEmpty())
                <li><span class="dropdown-item text-muted px-3">Belum ada satuan pendidikan</span></li>
            @endif
        </ul>
    </div>
</div>

<style>
.school-switcher .dropdown-item.active {
    color: #0d6efd !important;
}
.school-switcher .dropdown-item:hover {
    background-color: #f8f9fa;
    color: #212529;
}
.school-switcher .school-group-header:hover {
    background: transparent !important;
}
</style>
