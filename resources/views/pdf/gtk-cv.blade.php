<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>CV {{ $gtk->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color: #333; background: #fff; }
        @page { margin: 0; size: A4; }

        .page { width: 210mm; }

        /* HEADER */
        .header { background: #0f2d4a; color: #fff; padding: 7mm 10mm; }
        .htbl { width: 100%; border-collapse: collapse; }
        .hphoto { width: 30mm; padding-right: 8mm; vertical-align: middle; }
        .hinfo { vertical-align: middle; }
        .hphoto img { width: 34mm; height: 40mm; object-fit: cover; border: 2px solid rgba(255,255,255,0.3); border-radius: 2mm; }
        .hname { font-size: 18pt; font-weight: bold; margin-bottom: 1mm; }
        .hrole { font-size: 10pt; color: #b0d4f0; margin-bottom: 3mm; }
        .hmeta { font-size: 8pt; color: rgba(255,255,255,0.75); margin-bottom: 2mm; }
        .hmeta span { margin-right: 6mm; }
        .hbadges { }
        .badge {
            display: inline-block; font-size: 6.5pt; font-weight: bold;
            padding: 0.8mm 3mm; border-radius: 2mm; margin-right: 2mm;
        }
        .badge-on  { background: #1a7a3a; color: #6dffce; }
        .badge-off { background: #8b1a1a; color: #ffb3b3; }
        .badge-st  { background: #7a6010; color: #ffe66d; }
        .badge-jk  { background: rgba(255,255,255,0.12); color: rgba(255,255,255,0.9); }
        .badge-nupy{ background: #1a4a7a; color: #a8d8ff; }

        /* BODY */
        .body { width: 100%; border-collapse: collapse; }
        .sidebar { width: 58mm; background: #f4f7fb; padding: 5mm 5mm; vertical-align: top; border-right: 1px solid #d0dce8; }
        .maincol { padding: 5mm 6mm; vertical-align: top; }

        /* SIDEBAR */
        .s { margin-bottom: 5mm; }
        .st { font-size: 7pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.8px; color: #0f2d4a; margin-bottom: 2mm; padding-bottom: 1mm; border-bottom: 1.5px solid #0f2d4a; }
        .si { margin-bottom: 2mm; }
        .sl { font-size: 6pt; text-transform: uppercase; color: #8fa8c0; margin-bottom: 0.5mm; }
        .sv { font-size: 9pt; color: #333; line-height: 1.45; }
        .soc { display: block; font-size: 7.5pt; color: #555; margin-bottom: 1.5mm; }
        .wu { border: 1px solid #d0dce8; border-radius: 2mm; padding: 2mm; margin-bottom: 2mm; background: #fff; }
        .wud { width: 3mm; height: 3mm; border-radius: 50%; background: #0f2d4a; display: inline-block; vertical-align: middle; margin-right: 2mm; }
        .wud.p { background: #e67e22; }
        .wun { font-size: 8pt; font-weight: bold; color: #0f2d4a; }
        .wut { font-size: 6pt; color: #e67e22; font-weight: bold; margin-top: 0.5mm; }

        /* MAIN SECTIONS */
        .ms { margin-bottom: 6mm; }
        .stitle {
            font-size: 9.5pt; font-weight: bold; text-transform: uppercase;
            letter-spacing: 0.8px; color: #0f2d4a; margin-bottom: 2.5mm;
            padding-bottom: 1.5mm; border-bottom: 2px solid #0f2d4a;
            display: flex; align-items: center; gap: 2.5mm;
        }
        .sdot { width: 5mm; height: 5mm; border-radius: 50%; background: #e67e22; display: inline-block; flex-shrink: 0; }
        .sname { }

        /* KEPEGAWAIAN */
        .ec { border: 1px solid #d0dce8; border-left: 4px solid #0f2d4a; border-radius: 2mm; padding: 4mm; background: #f8fafd; }
        .er { display: table; width: 100%; }
        .ef { display: table-cell; padding: 1.5mm 4mm 1.5mm 0; }
        .el { font-size: 6pt; text-transform: uppercase; color: #8fa8c0; margin-bottom: 0.8mm; }
        .ev { font-size: 10pt; font-weight: bold; color: #0f2d4a; }
        .evs { font-size: 8.5pt; color: #555; font-weight: normal; }
        .ediv { border: none; border-top: 1px solid #d0dce8; margin: 3mm 0; }

        /* TABLE */
        .tbl { width: 100%; border-collapse: collapse; }
        .tbl th {
            background: #0f2d4a; color: #fff;
            font-size: 7pt; text-transform: uppercase; letter-spacing: 0.5px;
            padding: 2mm 2.5mm; text-align: left; font-weight: bold;
        }
        .tbl td {
            padding: 2mm 2.5mm; border-bottom: 1px solid #e8eef4;
            font-size: 9pt; color: #333; vertical-align: top;
        }
        .tbl tr:nth-child(even) td { background: #f8fafd; }
        .tbl tr:last-child td { border-bottom: none; }
        .bj { background: #e8f0f8; color: #0f2d4a; font-size: 7.5pt; font-weight: bold; padding: 1mm 2.5mm; border-radius: 1.5mm; }
        .bl { background: #d4edda; color: #155724; font-size: 6.5pt; font-weight: bold; padding: 0.8mm 2mm; border-radius: 1.5mm; }
        .bbl{ background: #fff3cd; color: #856404; font-size: 6.5pt; font-weight: bold; padding: 0.8mm 2mm; border-radius: 1.5mm; }

        /* PELATIHAN */
        .pi { display: flex; gap: 3mm; padding: 2.5mm 0; border-bottom: 0.5px solid #e8eef4; align-items: flex-start; }
        .pi:last-child { border-bottom: none; }
        .pdot { width: 3.5mm; height: 3.5mm; border-radius: 50%; background: #e67e22; flex-shrink: 0; margin-top: 2mm; }
        .pbody { flex: 1; }
        .pname { font-size: 9.5pt; font-weight: bold; color: #0f2d4a; }
        .pmeta { font-size: 7.5pt; color: #888; margin-top: 1mm; }
        .pmeta span { margin-right: 4mm; }
        .pmeta span:first-child { color: #555; font-weight: bold; }
        .bbl2 { background: #e8f0f8; color: #0f2d4a; font-size: 6.5pt; font-weight: bold; padding: 0.8mm 2mm; border-radius: 1.5mm; }

        /* KOMPETENSI */
        .kb { background: #0f2d4a; color: #fff; font-size: 8pt; font-weight: bold; padding: 1.5mm 4mm; border-radius: 2mm; margin: 1.5mm 2mm 1.5mm 0; display: inline-block; }

        /* RIWAYAT JABATAN */
        .ri { border: 1px solid #d0dce8; border-left: 4px solid #e67e22; border-radius: 2mm; padding: 3mm; background: #f8fafd; margin-bottom: 3mm; }
        .rd { width: 4mm; height: 4mm; border-radius: 50%; background: #0f2d4a; display: inline-block; vertical-align: top; margin-right: 2.5mm; margin-top: 1.5mm; }
        .rt { font-size: 9.5pt; font-weight: bold; color: #0f2d4a; }
        .rp { font-size: 7.5pt; color: #888; margin-top: 1mm; }

        /* TUGAS */
        .tg { display: table; width: 100%; }
        .tc { display: table-cell; width: 50%; padding: 1.5mm; }
        .tcd { border: 1px solid #d0dce8; border-radius: 2mm; padding: 3mm; background: #f8fafd; border-top: 3px solid #0f2d4a; }
        .tn { font-size: 9pt; font-weight: bold; color: #0f2d4a; }
        .th { font-size: 7.5pt; color: #888; margin-top: 1mm; }

        /* ALAMAT */
        .ag { display: table; width: 100%; }
        .ac { display: table-cell; width: 50%; padding: 1.5mm; }
        .acd { border: 1px solid #d0dce8; border-radius: 2mm; padding: 3.5mm; background: #f8fafd; }
        .acd.ktp { border-top: 3px solid #27ae60; }
        .acd.dom { border-top: 3px solid #0f2d4a; }
        .at { font-size: 7.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2.5mm; }
        .at.ktp { color: #27ae60; }
        .at.dom { color: #0f2d4a; }
        .atext { font-size: 8.5pt; color: #444; line-height: 1.6; }

        /* KELUARGA */
        .fg { display: table; width: 100%; }
        .fc { display: table-cell; width: 33.33%; padding: 1.5mm; }
        .fcd { border: 1px solid #d0dce8; border-radius: 2mm; padding: 3.5mm 2.5mm; text-align: center; background: #fafcff; }
        .fi { width: 12mm; height: 12mm; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12pt; margin-bottom: 2mm; font-weight: bold; }
        .fi.m { background: #dbeafe; color: #1d4ed8; }
        .fi.f { background: #fce7f3; color: #be185d; }
        .fn { font-size: 8.5pt; font-weight: bold; color: #0f2d4a; }
        .frel { font-size: 7pt; color: #888; margin-top: 1mm; }
        .finf { font-size: 7pt; color: #666; margin-top: 1mm; line-height: 1.4; }

        /* FOOTER */
        .footer { background: #0f2d4a; color: rgba(255,255,255,0.6); padding: 3mm 10mm; font-size: 6.5pt; display: table; width: 100%; }
        .fb { color: #fff; font-weight: bold; font-size: 8pt; display: table-cell; }
        .fr { display: table-cell; text-align: right; }

        /* EMPTY */
        .empty { font-size: 9pt; color: #ccc; font-style: italic; padding: 3mm; text-align: center; border: 1px dashed #d0dce8; border-radius: 2mm; }
    </style>
</head>
<body>

<table class="page" cellpadding="0" cellspacing="0" border="0">
<tbody>

    {{-- HEADER --}}
    <tr><td class="header">
        <table class="htbl" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td class="hphoto" align="center">
                <img src="{{ $avatarBase64 ?? 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=' }}" alt="Foto">
            </td>
            <td class="hinfo">
                <div class="hname">{{ $gtk->name }}</div>
                <div class="hrole">
                    {{ $gtk->employment?->jabatan ?? '–' }}
                    @if($gtk->employment?->jenis_gtk) &nbsp;&middot;&nbsp; {{ $gtk->employment->jenis_gtk }} @endif
                </div>
                <div class="hmeta">
                    @if($gtk->gtkContact?->no_hp)
                    <span>&#x1F4F1; {{ $gtk->gtkContact->no_hp }}</span>@endif
                    @if($gtk->email)
                    <span>&#x2709; {{ $gtk->email }}</span>@endif
                    @if($gtk->gtkProfile?->tempat_lahir && $gtk->gtkProfile?->tanggal_lahir)
                    <span>&#x1F4CD; {{ $gtk->gtkProfile->tempat_lahir }}, {{ \Carbon\Carbon::parse($gtk->gtkProfile->tanggal_lahir)->format('d/m/Y') }} ({{ $gtk->gtkProfile->tanggal_lahir->age }} th)</span>@endif
                </div>
                <div class="hbadges">
                    <span class="badge {{ $gtk->is_active ? 'badge-on' : 'badge-off' }}">{{ $gtk->is_active ? 'AKTIF' : 'NONAKTIF' }}</span>
                    @if($gtk->employment?->status_kepegawaian)
                    <span class="badge badge-st">{{ $gtk->employment->status_kepegawaian }}</span>@endif
                    @if($gtk->gtkProfile?->jenis_kelamin)
                    <span class="badge badge-jk">{{ $gtk->gtkProfile->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' }}</span>@endif
                    @if($gtk->employment?->nupy)
                    <span class="badge badge-nupy">NUPY: {{ $gtk->employment->nupy }}</span>@endif
                </div>
            </td>
        </tr>
        </table>
    </td></tr>

    {{-- BODY --}}
    <tr><td>
        <table class="body" cellpadding="0" cellspacing="0" border="0">
        <tr>
            {{-- SIDEBAR --}}
            <td class="sidebar">

                @if($gtk->gtkContact?->no_hp || $gtk->email || $gtk->gtkContact?->no_whatsapp)
                <div class="s">
                    <div class="st">Kontak</div>
                    @if($gtk->gtkContact?->no_hp)
                    <div class="si"><div class="sl">No. HP</div><div class="sv">{{ $gtk->gtkContact->no_hp }}</div></div>@endif
                    @if($gtk->gtkContact?->no_whatsapp)
                    <div class="si"><div class="sl">WhatsApp</div><div class="sv">{{ $gtk->gtkContact->no_whatsapp }}</div></div>@endif
                    @if($gtk->email)
                    <div class="si"><div class="sl">Email</div><div class="sv" style="font-size:8pt;">{{ $gtk->email }}</div></div>@endif
                </div>
                @endif

                @if($gtk->gtkContact?->instagram || $gtk->gtkContact?->facebook || $gtk->gtkContact?->twitter)
                <div class="s">
                    <div class="st">Sosial Media</div>
                    @if($gtk->gtkContact?->instagram)
                    <div class="soc">&#x1F4F8; {{ $gtk->gtkContact->instagram }}</div>@endif
                    @if($gtk->gtkContact?->facebook)
                    <div class="soc">&#x1D54; {{ $gtk->gtkContact->facebook }}</div>@endif
                    @if($gtk->gtkContact?->twitter)
                    <div class="soc">&#x1F4F9; {{ $gtk->gtkContact->twitter }}</div>@endif
                </div>
                @endif

                @if($gtk->gtkProfile?->golongan_darah || $gtk->gtkProfile?->agama || $gtk->gtkProfile?->status_perkawinan || $gtk->gtkContact?->kontak_darurat)
                <div class="s">
                    <div class="st">Data Pribadi</div>
                    @if($gtk->gtkProfile?->golongan_darah)
                    <div class="si"><div class="sl">Gol. Darah</div><div class="sv">{{ $gtk->gtkProfile->golongan_darah }}</div></div>@endif
                    @if($gtk->gtkProfile?->agama)
                    <div class="si"><div class="sl">Agama</div><div class="sv">{{ ucfirst($gtk->gtkProfile->agama) }}</div></div>@endif
                    @if($gtk->gtkProfile?->status_perkawinan)
                    <div class="si"><div class="sl">Status</div><div class="sv">{{ $gtk->gtkProfile->status_perkawinan }}</div></div>@endif
                    @if($gtk->gtkContact?->kontak_darurat)
                    <div class="si"><div class="sl">Kontak Darurat</div><div class="sv">{{ $gtk->gtkContact->kontak_darurat }}</div></div>@endif
                </div>
                @endif

                @if($gtk->workUnits && $gtk->workUnits->count() > 0)
                <div class="s">
                    <div class="st">Satuan Kerja</div>
                    @foreach($gtk->workUnits as $wu)
                    <div class="wu">
                        <div class="wud {{ $wu->is_primary ? 'p' : '' }}"></div>
                        <div style="display:inline-block;vertical-align:top;">
                            <div class="wun">{{ $wu->workUnit->name ?? '–' }}</div>
                            @if($wu->is_primary)<div class="wut">&#x2605; UTAMA</div>@endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                @if($domisiliAddress)
                <div class="s">
                    <div class="st">Alamat Domisili</div>
                    <div class="si">
                        <div class="sl">Alamat</div>
                        <div class="sv" style="font-size:8pt; line-height:1.5;">
                            {{ $domisiliAddress->jalan }}<br>
                            @if($domisiliAddress->rt_rw)RT/RW: {{ $domisiliAddress->rt_rw }}<br>@endif
                            @if($domisiliAddress->dusun)Ds. {{ $domisiliAddress->dusun }}<br>@endif
                            {{ $domisiliAddress->desa }}<br>
                            {{ $domisiliAddress->kecamatan }}<br>
                            {{ $domisiliAddress->kab_kota }}, {{ $domisiliAddress->provinsi }}
                            @if($domisiliAddress->kode_pos)<br>{{ $domisiliAddress->kode_pos }}@endif
                        </div>
                    </div>
                </div>
                @endif

            </td>

            {{-- MAIN --}}
            <td class="maincol">

                @if($gtk->employment)
                <div class="ms">
                    <div class="stitle"><div class="sdot"></div><div class="sname">Data Kepegawaian</div></div>
                    <div class="ec">
                        <div class="er">
                            <div class="ef"><div class="el">Jenis GTK</div><div class="ev">{{ $gtk->employment->jenis_gtk ?? '–' }}</div></div>
                            <div class="ef"><div class="el">Jabatan</div><div class="ev">{{ $gtk->employment->jabatan ?? '–' }}</div></div>
                            <div class="ef"><div class="el">Status</div><div class="ev">{{ $gtk->employment->status_kepegawaian ?? '–' }}</div></div>
                            <div class="ef"><div class="el">TMT</div><div class="ev">{{ $gtk->employment->tmt ? \Carbon\Carbon::parse($gtk->employment->tmt)->format('d/m/Y') : '–' }}</div></div>
                        </div>
                        @if($gtk->employment->tanggal_sk || $gtk->employment->nomor_sk)
                        <hr class="ediv">
                        <div class="er">
                            @if($gtk->employment->tanggal_sk)
                            <div class="ef"><div class="el">Tanggal SK</div><div class="ev">{{ \Carbon\Carbon::parse($gtk->employment->tanggal_sk)->format('d/m/Y') }}</div></div>@endif
                            @if($gtk->employment->nomor_sk)
                            <div class="ef" style="width:50%;"><div class="el">Nomor SK</div><div class="ev evs">{{ $gtk->employment->nomor_sk }}</div></div>@endif
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <div class="ms">
                    <div class="stitle"><div class="sdot"></div><div class="sname">Riwayat Pendidikan</div></div>
                    @if($gtk->educations && $gtk->educations->count() > 0)
                    <table class="tbl">
                        <thead><tr>
                            <th width="12%">Jenjang</th>
                            <th width="26%">Institusi</th>
                            <th width="22%">Jurusan / Fak.</th>
                            <th width="7%">Thn</th>
                            <th width="14%">Nilai/IPK</th>
                            <th width="19%">Status</th>
                        </tr></thead>
                        <tbody>
                            @foreach($gtk->educations->sortByDesc('tahun_lulus') as $edu)
                            <tr>
                                <td><span class="bj">{{ $edu->jenjang_pendidikan }}</span></td>
                                <td><strong>{{ $edu->nama_satuan_pendidikan }}</strong></td>
                                <td>
                                    @if($edu->jurusan || $edu->fakultast)
                                        {{ $edu->jurusan }}
                                        @if($edu->jurusan && $edu->fakultast) &ndash; @endif
                                        {{ $edu->fakultast }}
                                    @else &ndash; @endif
                                </td>
                                <td>{{ $edu->tahun_lulus ?? '–' }}</td>
                                <td>
                                    @if($edu->nilai_akhir)
                                        {{ rtrim(rtrim(number_format($edu->nilai_akhir, 2), '0'), '.') }}
                                        /{{ $edu->skala_nilai == 4 ? '4.00' : '100' }}
                                    @else &ndash; @endif
                                </td>
                                <td>
                                    @if($edu->status == 'LULUS')
                                        <span class="bl">LULUS</span>
                                    @else
                                        <span class="bbl">{{ str_replace('_', ' ', $edu->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="empty">Belum ada data pendidikan.</div>
                    @endif
                </div>

                @if($gtk->trainings && $gtk->trainings->count() > 0)
                <div class="ms">
                    <div class="stitle"><div class="sdot"></div><div class="sname">Pelatihan &amp; Diklat</div></div>
                    @foreach($gtk->trainings->sortByDesc('tahun') as $pelatihan)
                    <div class="pi">
                        <div class="pdot"></div>
                        <div class="pbody">
                            <div class="pname">{{ $pelatihan->nama_pelatihan }}</div>
                            <div class="pmeta">
                                <span>{{ $pelatihan->penyelenggara ?? '–' }}</span>
                                @if($pelatihan->bidang_pelatihan)
                                <span class="bbl2">{{ $pelatihan->bidang_pelatihan }}</span>@endif
                                <span style="color:#0f2d4a;font-weight:bold;">{{ $pelatihan->tahun ?? '–' }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                @if($gtk->competencies && $gtk->competencies->count() > 0)
                <div class="ms">
                    <div class="stitle"><div class="sdot"></div><div class="sname">Kompetensi</div></div>
                    @foreach($gtk->competencies as $kompetensi)
                    <span class="kb">{{ $kompetensi->bidang_kompetensi }}</span>
                    @endforeach
                </div>
                @endif

                @if($gtk->careerPaths && $gtk->careerPaths->count() > 0)
                <div class="ms">
                    <div class="stitle"><div class="sdot"></div><div class="sname">Riwayat Jabatan</div></div>
                    @foreach($gtk->careerPaths->sortBy('tmt') as $cp)
                    <div class="ri">
                        <div class="rd"></div>
                        <div style="display:inline-block;vertical-align:top;">
                            <div class="rt">{{ $cp->jabatan_fungsi }}</div>
                            <div class="rp">
                                {{ $cp->tmt ? \Carbon\Carbon::parse($cp->tmt)->format('d/m/Y') : '–' }}
                                &ndash;
                                @if($cp->tst) {{ \Carbon\Carbon::parse($cp->tst)->format('d/m/Y') }}
                                @else Sekarang @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                @if($gtk->additionalTasks && $gtk->additionalTasks->count() > 0)
                <div class="ms">
                    <div class="stitle"><div class="sdot"></div><div class="sname">Tugas Tambahan</div></div>
                    <div class="tg">
                        @foreach($gtk->additionalTasks as $tugas)
                        <div class="tc">
                            <div class="tcd">
                                <div class="tn">{{ $tugas->nama_tugas }}</div>
                                @if($tugas->hours_per_week)
                                <div class="th">&#x23F1; {{ $tugas->hours_per_week }} jam/minggu</div>@endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($gtk->gtkProfile && $gtk->gtkProfile->familyMembers && $gtk->gtkProfile->familyMembers->count() > 0)
                <div class="ms">
                    <div class="stitle"><div class="sdot"></div><div class="sname">Data Keluarga</div></div>
                    <div class="fg">
                        @foreach($gtk->gtkProfile->familyMembers as $anggota)
                        <div class="fc">
                            <div class="fcd">
                                <div class="fi {{ $anggota->jenis_kelamin == 'L' ? 'm' : 'f' }}">
                                    {{ $anggota->jenis_kelamin == 'L' ? '&#x2642;' : '&#x2640;' }}
                                </div>
                                <div class="fn">{{ $anggota->nama }}</div>
                                <div class="frel">{{ ucfirst($anggota->relationship) }}</div>
                                @if($anggota->pekerjaan)<div class="finf">{{ $anggota->pekerjaan }}</div>@endif
                                @if($anggota->tanggal_lahir)
                                <div class="finf">{{ $anggota->tanggal_lahir->age }} th ({{ \Carbon\Carbon::parse($anggota->tanggal_lahir)->format('d/m/Y') }})</div>@endif
                                @if($anggota->pendidikan_terakhir)<div class="finf">{{ $anggota->pendidikan_terakhir }}</div>@endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

            </td>
        </tr>
        </table>
    </td></tr>

    {{-- FOOTER --}}
    <tr><td class="footer">
        <div class="fb">Alim &nbsp;|&nbsp; Curriculum Vitae &mdash; {{ $gtk->name }}</div>
        <div class="fr">Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} WIB</div>
    </td></tr>

</tbody>
</table>

</body>
</html>