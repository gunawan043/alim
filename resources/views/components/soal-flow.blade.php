@props(['current' => 'kisi', 'userId' => null])

@php
    $userId = $userId ?? request()->route('userId') ?? auth()->id();
    $steps = [
        'kisi' => ['label' => 'Kisi-kisi', 'route' => 'user.kisi-kisi-soal.index', 'icon' => 'ri-file-list-3-line'],
        'bank' => ['label' => 'Bank & Input Soal', 'route' => 'user.bank-soal.index', 'icon' => 'ri-archive-2-line'],
        'paket' => ['label' => 'Paket & Validasi', 'route' => 'user.paket-soal.index', 'icon' => 'ri-stack-line'],
        'cetak' => ['label' => 'Cetak Naskah', 'route' => 'user.paket-soal.index', 'icon' => 'ri-printer-line'],
    ];
@endphp

<div class="card border-0 bg-light mb-3">
    <div class="card-body py-2 px-3">
        <div class="d-flex flex-wrap align-items-center gap-2 small">
            <span class="fw-semibold text-muted me-1"><i class="ri-route-line me-1"></i>Alur:</span>
            @foreach($steps as $key => $step)
                @php $done = array_search($current, array_keys($steps), true) > array_search($key, array_keys($steps), true); @endphp
                <a href="{{ route($step['route'], ['userId' => $userId]) }}"
                   class="badge text-decoration-none {{ $current === $key ? 'bg-primary text-white' : ($done ? 'bg-success-subtle text-success' : 'bg-white text-muted border') }}">
                    <i class="{{ $step['icon'] }} me-1"></i>{{ $loop->iteration }}. {{ $step['label'] }}
                </a>
                @if(! $loop->last)<i class="ri-arrow-right-s-line text-muted"></i>@endif
            @endforeach
        </div>
        <div class="text-muted mt-1" style="font-size:.72rem">
            Urutan: susun kisi-kisi → isi soal di bank → bentuk paket → validasi/publish → cetak naskah.
        </div>
    </div>
</div>
