{{-- resources/views/components/asrama-context-bar.blade.php --}}
{{--
  Asrama Context Info Bar — untuk Super Admin sidebar.
  Muncul hanya saat user sedang di halaman konteks asrama (ada asramaUuid di route).
  Menampilkan nama asrama aktif + link ke daftar asrama.
--}}
@php
$asrama = $asramaContext ?? null;
$currentUrl = url()->current();
@endphp

@if($asrama)
<div class="asrama-context-bar mb-2 px-2">
    {{-- Label --}}
    <div class="d-flex align-items-center gap-1 mb-1" style="font-size:0.7rem; color: #878a99; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">
        <i class="ri-hotel-fill" style="font-size:0.8rem;"></i>
        <span>Asrama Aktif</span>
        <span class="badge bg-info ms-auto" style="font-size:0.6rem; padding: 1px 5px;">Context</span>
    </div>

    {{-- Asrama Info Card --}}
    <div class="d-flex align-items-center gap-2 p-2 rounded"
         style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-size:0.8rem;">
        <i class="ri-hotel-fill text-white" style="font-size:1.1rem;"></i>
        <div class="flex-grow-1 overflow-hidden">
            <div class="text-white fw-semibold text-truncate" style="font-size:0.8rem;">
                {{ $asrama->name ?? 'Asrama' }}
            </div>
            @if($asrama->school)
                <div class="text-white-50 text-truncate" style="font-size:0.7rem;">
                    {{ $asrama->school->name }}
                </div>
            @endif
        </div>
        <a href="{{ route('user.asrama.index', ['userId' => auth()->id()]) }}"
           class="btn btn-sm btn-light rounded-pill px-2 flex-shrink-0"
           style="font-size:0.65rem;"
           title="Kembali ke Daftar Asrama">
            <i class="ri-arrow-left-right-line"></i>
        </a>
    </div>

    {{-- Current module indicator --}}
    @if($currentAsramaModule)
    <div class="mt-1 px-1 d-flex align-items-center gap-1" style="font-size:0.7rem; color: #878a99;">
        <i class="ri-arrow-right-s-line"></i>
        <span class="text-truncate">{{ $currentAsramaModule }}</span>
    </div>
    @endif
</div>
@endif
