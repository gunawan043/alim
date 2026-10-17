{{-- Empty state bersama — pola gtk/index: lord-icon + judul + pesan + aksi opsional.

     Pemakaian di dalam <tbody> tabel (renders <tr><td>):
        <x-empty-state colspan="8" title="Belum ada mapel" message="Tambahkan mapel untuk memulai">
            <a href="..." class="btn btn-primary mt-3">Tambah Mapel</a>
        </x-empty-state>

     Pemakaian di luar tabel (renders <div>):
        <x-empty-state icon="https://cdn.lordicon.com/msoeawqm.json" title="Belum ada aktivitas" />
--}}
@props([
    'colspan' => null,
    'icon' => 'https://cdn.lordicon.com/msoeawqm.json',
    'title' => 'Belum ada data',
    'message' => null,
    'primaryColor' => '#121331',
    'secondaryColor' => '#08a88a',
])

@once
    @push('scripts')
        <script src="https://cdn.lordicon.com/lordicon.js"></script>
    @endpush
@endonce

@if($colspan)
    <tr>
        <td colspan="{{ $colspan }}" class="text-center py-5">
            <lord-icon src="{{ $icon }}" trigger="loop"
                colors="primary:{{ $primaryColor }},secondary:{{ $secondaryColor }}"
                style="width:75px;height:75px"></lord-icon>
            <h5 class="mt-2">{{ $title }}</h5>
            @if($message)
                <p class="text-muted mb-0">{{ $message }}</p>
            @endif
            {{ $slot }}
        </td>
    </tr>
@else
    <div class="text-center py-5">
        <lord-icon src="{{ $icon }}" trigger="loop"
            colors="primary:{{ $primaryColor }},secondary:{{ $secondaryColor }}"
            style="width:75px;height:75px"></lord-icon>
        <h5 class="mt-2">{{ $title }}</h5>
        @if($message)
            <p class="text-muted mb-0">{{ $message }}</p>
        @endif
        {{ $slot }}
    </div>
@endif
