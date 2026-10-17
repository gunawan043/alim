@extends('cetak.layout')

@section('title', 'RPM')
@section('doc_title', 'Rencana Pembelajaran Mendalam (RPM)')
@section('doc_subtitle')
    {{ $perangkat->judul }}
@endsection

@section('content')
    @php
        $tipeLabel = \App\Models\PerangkatPembelajaran::TIPE_OPTIONS[$perangkat->tipe] ?? 'RPM';
        $atp = $perangkat->atp;
        $tps = $atp?->items?->map(fn ($i) => $i->tujuanPembelajaran)->filter() ?? collect();
        $cps = $tps->map(fn ($tp) => $tp->capaianPembelajaran)->filter()->unique('id');

        $renderText = function (?string $text) {
            return $value = $text
                ? nl2br(e($text))
                : '<span class="muted">—</span>';
        };
    @endphp

    <div class="section-title">Informasi Umum</div>
    <div class="section-body">
        <table class="inside">
            <tr>
                <td class="label">Satuan Pendidikan</td><td>: {{ $school?->name ?? '-' }}</td>
                <td class="label">Guru Penyusun</td><td>: {{ $perangkat->teacher?->name ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Mata Pelajaran</td><td>: {{ $perangkat->subject?->name ?? '-' }}</td>
                <td class="label">Kelas / Fase</td><td>: {{ $perangkat->studyGroup?->name ?? $perangkat->gradeLevel?->name ?? 'Semua Kelas' }}{{ $perangkat->gradeLevel?->fase ? ' / '.$perangkat->gradeLevel->fase : '' }}</td>
            </tr>
            <tr>
                <td class="label">Tahun Ajaran</td><td>: {{ $perangkat->academicYear?->name ?? '-' }}</td>
                <td class="label">Semester</td><td>: {{ ucfirst($perangkat->semester) }}</td>
            </tr>
            <tr>
                <td class="label">Tipe RPM</td><td>: {{ $tipeLabel }}</td>
                <td class="label">ATP Acuan</td><td>: {{ $atp?->subject?->name ?? '-' }} {{ $atp ? '('.$atp->total_jp.' JP)' : '' }}</td>
            </tr>
        </table>
    </div>

    @if($cps->isNotEmpty())
        <div class="section-title">Capaian Pembelajaran (CP)</div>
        <div class="section-body">
            @foreach($cps as $cp)
                <p>
                    <strong>[Fase {{ $cp->fase }}{{ $cp->elemen ? ' — '.$cp->elemen : '' }}]</strong>
                    {{ $cp->deskripsi }}
                </p>
            @endforeach
        </div>
    @endif

    @if($tps->isNotEmpty())
        <div class="section-title">Tujuan Pembelajaran (TP) — dari ATP</div>
        <div class="section-body">
            <table class="inside">
                @foreach($tps as $tp)
                    <tr>
                        <td style="width:70px"><strong>{{ $tp->kode_tp }}</strong></td>
                        <td>{{ $tp->deskripsi }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    @foreach($groups as $group)
        @php
            $filledKeys = collect($group['keys'])->filter(fn ($key) => $perangkat->desainValue($key));
        @endphp
        <div class="section-title">{{ $group['label'] }}</div>
        <div class="section-body">
            @if($filledKeys->isEmpty())
                <p class="muted">Belum diisi.</p>
            @else
                <table class="inside">
                    @foreach($filledKeys as $key)
                        <tr>
                            <td class="label">{{ \App\Models\PerangkatPembelajaran::SECTIONS_RPM[$key] ?? (\App\Models\PerangkatPembelajaran::DESAIN_SECTIONS[$key] ?? $key) }}</td>
                            <td>{!! $renderText($perangkat->desainValue($key)) !!}</td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </div>
    @endforeach

    @if(! empty($legacyFilled))
        <div class="section-title">Fondasi Pembelajaran Mendalam</div>
        <div class="section-body">
            <table class="inside">
                @foreach($legacyFilled as $key => $label)
                    <tr>
                        <td class="label">{{ $label }}</td>
                        <td>{!! $renderText($perangkat->desainValue($key)) !!}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    <table class="sign">
        <tr>
            <td style="width:50%">
                Mengetahui,<br>Kepala Satuan Pendidikan
                <div class="space"></div>
                <div class="name">{{ $school?->principal_name ?? '................................' }}</div>
                @if($school?->principal_nip)<div class="nip">NIP. {{ $school->principal_nip }}</div>@endif
            </td>
            <td style="width:50%">
                {{ now()->locale('id')->translatedFormat('d F Y') }}<br>Guru Mata Pelajaran
                <div class="space"></div>
                <div class="name">{{ $perangkat->teacher?->name ?? '................................' }}</div>
            </td>
        </tr>
    </table>
@endsection
