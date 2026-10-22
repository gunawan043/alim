<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Hadir Mufradat — {{ $record->title }}</title>
    @include('bahasa._styles')
</head>
<body class="lang-print p-4">
    <div class="text-center mb-3">
        <h4 class="mb-1">DAFTAR HADIR PESERTA KEGIATAN HAFALAN MUFRADAT</h4>
        <div>Departemen Bahasa · PAH Mataram</div>
    </div>

    <table class="mb-3" style="border:0">
        <tr style="border:0">
            <td style="border:0;width:120px">Bulan</td><td style="border:0">: ______________________________</td>
            <td style="border:0;width:120px">Pekan</td><td style="border:0">: ______________________________</td>
        </tr>
        <tr style="border:0">
            <td style="border:0">Waktu</td><td style="border:0">: ______________________________</td>
            <td style="border:0">Tempat</td><td style="border:0">: ______________________________</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width:38px">No</th>
                <th>Nama Peserta</th>
                <th style="width:14%">Kelas</th>
                <th style="width:17%">Hari/Tanggal</th>
                <th style="width:20%">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @for($i = 1; $i <= 25; $i++)
                <tr>
                    <td class="text-center">{{ $i }}</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            @endfor
        </tbody>
    </table>

    <div class="row mt-4" style="font-size:12px">
        <div class="col-6 text-center">
            <div>Mustami'</div>
            <div style="height:70px"></div>
            <div>__________________________</div>
        </div>
        <div class="col-6 text-center">
            <div>Qismul Lughah</div>
            <div style="height:70px"></div>
            <div>__________________________</div>
        </div>
    </div>

    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn btn-primary">Cetak</button>
    </div>
</body>
</html>
