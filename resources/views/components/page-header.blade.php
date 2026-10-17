{{-- Usage:
   @component('components.page-header')
       @slot('title') Judul Halaman @endslot
       @slot('description') Deskripsi singkat @endslot
       @slot('category') Cuti & Izin @endslot
       @slot('icon') ri-calendar-check-line @endslot
       @slot('color') success @endslot  {{-- primary|success|warning|danger|info|secondary|purple --}}
       @slot('actions')
           <a href="..." class="btn btn-primary btn-sm">Tambah</a>
       @endslot
   @endcomponent
--}}
@php
$colorMap = [
    'primary'   => ['bg' => 'bg-primary-subtle',        'text' => 'text-primary',        'border' => 'border-primary'],
    'success'   => ['bg' => 'bg-success-subtle',        'text' => 'text-success',        'border' => 'border-success'],
    'warning'   => ['bg' => 'bg-warning-subtle',        'text' => 'text-warning',        'border' => 'border-warning'],
    'danger'    => ['bg' => 'bg-danger-subtle',         'text' => 'text-danger',         'border' => 'border-danger'],
    'info'      => ['bg' => 'bg-info-subtle',           'text' => 'text-info',           'border' => 'border-info'],
    'secondary' => ['bg' => 'bg-secondary-subtle',      'text' => 'text-secondary',      'border' => 'border-secondary'],
    'purple'    => ['bg' => 'bg-purple-subtle',         'text' => 'text-purple',         'border' => 'border-purple'],
    'dark'      => ['bg' => 'bg-dark-subtle',           'text' => 'text-dark',           'border' => 'border-dark'],
];
$color = $colorMap[$color ?? 'primary'] ?? $colorMap['primary'];
@endphp

<div class="row align-items-center g-3 mb-4">
    {{-- Title block --}}
    <div class="col-sm-auto">
        <div class="d-flex align-items-center gap-3">
            @if(isset($icon))
            <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title rounded fs-3 {!! $color['bg'] !!} {!! $color['text'] !!}">
                    <i class="{{ $icon }}"></i>
                </span>
            </div>
            @endif
            <div>
                @if(isset($category))
                <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:0.65rem; letter-spacing:0.08em;">{{ $category }}</p>
                @endif
                <h4 class="mb-0 fw-bold">{{ $title ?? '' }}</h4>
                @if(isset($description))
                <p class="text-muted mb-0" style="font-size:0.8rem;">{{ $description }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Actions block --}}
    @if(isset($actions) || isset($filters))
    <div class="col-sm-auto ms-auto">
        <div class="d-flex gap-2 flex-wrap align-items-center">
            @if(isset($filters))
                {{ $filters }}
            @endif
            @if(isset($actions))
                {{ $actions }}
            @endif
        </div>
    </div>
    @endif
</div>
