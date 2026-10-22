<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Berita Acara Pembinaan Bahasa</title>
    @include('bahasa._styles')
</head>
<body class="lang-print p-5">
    <div class="text-center mb-4">
        <h4 class="mb-1">BERITA ACARA PEMBINAAN BAHASA</h4>
        <div>Departemen Bahasa · PAH Mataram</div>
    </div>

    <p>Saya yang bertanda tangan di bawah ini:</p>
    <table style="border:0;margin-bottom:18px">
        <tr style="border:0"><td style="border:0;width:150px">Nama</td><td style="border:0">: {{ $record->student?->name ?? '__________________________' }}</td></tr>
        <tr style="border:0"><td style="border:0">Kelas/Kamar</td><td style="border:0">: {{ $record->class_name ?: ($record->dormitory?->name ?? '__________________________') }}</td></tr>
        <tr style="border:0"><td style="border:0">Tanggal</td><td style="border:0">: {{ $record->violation_date?->format('d/m/Y') ?? '__________________________' }}</td></tr>
        <tr style="border:0"><td style="border:0">Pelanggaran</td><td style="border:0">: {{ $record->violation_type }}</td></tr>
        <tr style="border:0"><td style="border:0">Poin</td><td style="border:0">: {{ $record->points }}</td></tr>
    </table>

    <p>Telah melakukan pelanggaran terhadap aturan wajib berbahasa Arab di lingkungan asrama PAH Mataram. Dengan ini saya berjanji untuk:</p>
    <ol>
        <li>Menaati peraturan wajib berbahasa Arab.</li>
        <li>Melaksanakan semua bentuk pembinaan yang telah ditetapkan.</li>
        <li>Tidak mengulangi pelanggaran serupa.</li>
    </ol>
    <p>Jika saya melanggar kembali, saya bersedia menerima sanksi lanjutan yang berlaku di PAH Mataram.</p>

    <div class="row mt-5" style="font-size:12px">
        <div class="col-6 text-center">
            <div>Santri yang bersangkutan</div>
            <div style="height:80px"></div>
            <div>__________________________</div>
        </div>
        <div class="col-6 text-center">
            <div>Mataram, {{ now()->format('d/m/Y') }}</div>
            <div>Pembina / Departemen Bahasa</div>
            <div style="height:80px"></div>
            <div>__________________________</div>
        </div>
    </div>

    <div class="text-center mt-4 no-print"><button onclick="window.print()" class="btn btn-primary">Cetak</button></div>
</body>
</html>
