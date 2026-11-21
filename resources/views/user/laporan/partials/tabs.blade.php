{{--
    Tab laporan: garis bawah, penanda emas pada tab aktif.
    Parameter: $tabs = [['route' => 'nama.route', 'label' => 'Teks'], ...]
--}}
@php
    $tabs = $tabs ?? [];
    $tabQuery = request()->query();
    unset($tabQuery['page']);
@endphp

@if (count($tabs))
    <nav class="lap-tabs" aria-label="Bagian laporan">
        @foreach ($tabs as $tab)
            @php $isActive = request()->routeIs($tab['route']); @endphp
            <a href="{{ route($tab['route'], array_merge(['userId' => $userId], $tabQuery)) }}"
               class="lap-tab {{ $isActive ? 'is-active' : '' }}"
               @if ($isActive) aria-current="page" @endif>{{ $tab['label'] }}</a>
        @endforeach
    </nav>
@endif
