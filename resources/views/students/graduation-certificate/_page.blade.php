@php
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];
    $tanggalLahir = $student->birth_date
        ? $student->birth_date->day . ' ' . $bulan[(int) $student->birth_date->month] . ' ' . $student->birth_date->year
        : '-';
    $tanggalLulus = $student->graduation_date
        ? $student->graduation_date->day . ' ' . $bulan[(int) $student->graduation_date->month] . ' ' . $student->graduation_date->year
        : '-';
    $kota = $setting->city ? format_city_name($setting->city) : ($school->city?->display_name ?? '');
    $leader = app(\App\Services\Documents\SchoolLeaderService::class)->forSchool($school);
    $penandaTangan = $setting->signer_name ?: $leader['display_name'];
    $nip = $setting->signer_nip ?: $leader['nupy'];
    $nomor = 0;
@endphp
<div class="page">
    @if ($setting->show_kop)
        <div class="kop">
            <div class="nama">{{ $school->kop_nama ?: $school->name }}</div>
            <div class="alamat">
                {{ $school->kop_alamat ?: $school->address }}
                @if ($school->kop_telp)
                    &nbsp;|&nbsp; Telp. {{ $school->kop_telp }}
                @endif
                @if ($school->kop_email)
                    &nbsp;|&nbsp; {{ $school->kop_email }}
                @endif
            </div>
        </div>
    @endif

    <div class="judul">{{ $setting->title }}</div>
    @if ($setting->number_prefix)
        <div class="nomor">Nomor: {{ $setting->number_prefix }}</div>
    @else
        <div class="nomor">&nbsp;</div>
    @endif

    <p>
        Yang bertanda tangan di bawah ini, {{ $setting->signer_jabatan }} {{ $school->name }},
        menerangkan bahwa:
    </p>

    <table class="identitas">
        <tr>
            <td style="width:74%">
                <table class="data">
                    <tr>
                        <td class="label">Satuan Pendidikan</td>
                        <td class="pemisah">:</td>
                        <td>{{ $school->name }}</td>
                    </tr>
                    @if ($setting->show_npsn)
                        <tr>
                            <td class="label">Nomor Pokok Satuan Pendidikan</td>
                            <td class="pemisah">:</td>
                            <td>{{ $school->kop_npsn ?: $school->npsn }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="label">Nama Lengkap</td>
                        <td class="pemisah">:</td>
                        <td>{{ strtoupper($student->name) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tempat, Tanggal Lahir</td>
                        <td class="pemisah">:</td>
                        <td>{{ $student->birth_place ?: '-' }}, {{ $tanggalLahir }}</td>
                    </tr>
                    <tr>
                        <td class="label">Nomor Induk Siswa Nasional</td>
                        <td class="pemisah">:</td>
                        <td>{{ $student->nisn ?: '-' }}</td>
                    </tr>
                    @if ($setting->show_ijazah)
                        <tr>
                            <td class="label">Nomor Ijazah</td>
                            <td class="pemisah">:</td>
                            <td>{{ $student->certificate_number ?: '-' }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="label">Tanggal Kelulusan</td>
                        <td class="pemisah">:</td>
                        <td>{{ $tanggalLulus }}</td>
                    </tr>
                    <tr>
                        <td class="label">Kurikulum</td>
                        <td class="pemisah">:</td>
                        <td>{{ $setting->curriculum }}</td>
                    </tr>
                    @if (! empty($kompetensi))
                        <tr>
                            <td class="label">Program Keahlian</td>
                            <td class="pemisah">:</td>
                            <td>{{ $kompetensi }}</td>
                        </tr>
                        <tr>
                            <td class="label">Konsentrasi Keahlian</td>
                            <td class="pemisah">:</td>
                            <td>{{ $kompetensi }}</td>
                        </tr>
                    @endif
                </table>
            </td>
            @if ($setting->show_photo)
                <td class="foto">
                    @if (! empty($photo))
                        <img src="{{ $photo }}" alt="Foto {{ $student->name }}">
                    @else
                        <span class="kosong"></span>
                    @endif
                </td>
            @endif
        </tr>
    </table>

    <p style="margin-top:10px">
        Dinyatakan <strong>LULUS</strong> dari Satuan Pendidikan
        @if ($setting->criteria_text)
            {{ $setting->criteria_text }}
        @else
            berdasarkan kriteria kelulusan {{ $school->name }} Tahun Ajaran {{ $graduationYear }}
        @endif
        @if ($setting->show_nilai)
            , dengan nilai sebagai berikut:
        @else
            .
        @endif
    </p>

    @if ($setting->show_nilai && $groups !== [])
        <table class="nilai">
            <thead>
                <tr>
                    <th style="width:8%">No</th>
                    <th>Mata Pelajaran</th>
                    <th style="width:15%">Nilai</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($groups as $group)
                    <tr class="grup">
                        <td class="tengah"></td>
                        <td>{{ $group['label'] }}</td>
                        <td></td>
                    </tr>
                    @foreach ($group['items'] as $item)
                        @php $nomor++; @endphp
                        <tr>
                            <td class="tengah">{{ $nomor }}.</td>
                            <td>{{ $item['name'] }}</td>
                            <td class="tengah">{{ $item['score'] !== null ? number_format($item['score'], 2, ',', '.') : '' }}</td>
                        </tr>
                    @endforeach
                @endforeach
                @if ($setting->show_rata_rata)
                    <tr class="grup">
                        <td colspan="2" class="kanan">Rata-Rata</td>
                        <td class="tengah">{{ $average !== null ? number_format($average, 2, ',', '.') : '' }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endif

    @if ($setting->show_signature)
        <table class="ttd">
            <tr>
                <td></td>
                <td class="kolom">
                    <div>{{ $kota ? $kota . ', ' : '' }}{{ $tanggalLulus }}</div>
                    <div>{{ $setting->signer_jabatan }}</div>
                    @php
                        $sigW = max((float) ($documentMedia['ttd_width'] ?? 30), (float) ($documentMedia['stamp_width'] ?? 30));
                        $sigH = max(
                            (float) ($documentMedia['ttd_margin'] ?? 2) + (float) ($documentMedia['ttd_height'] ?? 15),
                            (float) ($documentMedia['stamp_margin'] ?? 2) + (float) ($documentMedia['stamp_height'] ?? 30)
                        );
                    @endphp
                    <div style="position:relative;width:{{ $sigW }}mm;height:{{ $sigH }}mm;margin:2mm auto 0;">
                        @if (($documentMedia['show_stamp'] ?? true) && ! empty($documentMedia['stamp']))
                            <img src="{{ $documentMedia['stamp'] }}" alt="Stempel"
                                style="position:absolute;left:0;top:{{ (float) ($documentMedia['stamp_margin'] ?? 2) }}mm;width:{{ (float) ($documentMedia['stamp_width'] ?? 30) }}mm;height:{{ (float) ($documentMedia['stamp_height'] ?? 30) }}mm;object-fit:contain;opacity:.9;">
                        @endif
                        @if (($documentMedia['show_ttd_ksp'] ?? true) && ! empty($documentMedia['ttd_ksp']))
                            <img src="{{ $documentMedia['ttd_ksp'] }}" alt="TTD KSP"
                                style="position:absolute;left:0;top:{{ (float) ($documentMedia['ttd_margin'] ?? 2) }}mm;width:{{ (float) ($documentMedia['ttd_width'] ?? 30) }}mm;height:{{ (float) ($documentMedia['ttd_height'] ?? 15) }}mm;object-fit:contain;">
                        @endif
                    </div>
                    <div class="nama">{{ $penandaTangan ?: '................................' }}</div>
                    @if ($nip)
                        <div>NIP. {{ $nip }}</div>
                    @endif
                </td>
            </tr>
        </table>
    @endif

    @include('documents._qr-footer', ['verification' => $verification ?? null])
</div>
