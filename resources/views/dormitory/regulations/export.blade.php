<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ekspor Peraturan Asrama</title>
    <style>
        @page { size: A4 portrait; margin: 1cm; }
        body { font-family: 'Times New Roman', serif; font-size: 11pt; line-height: 1.5; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 16pt; font-weight: bold; }
        .header .subtitle { font-size: 12pt; color: #555; margin-top: 5px; }
        .regulation { margin-bottom: 30px; page-break-inside: avoid; }
        .regulation-title { font-weight: bold; font-size: 13pt; margin-bottom: 5px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        .regulation-category { font-size: 10pt; color: #666; margin-bottom: 10px; }
        .regulation-content { background: #f9f9f9; padding: 10px; border-left: 3px solid #007bff; margin-top: 10px; }
        .footer { text-align: center; margin-top: 30px; font-size: 10pt; color: #777; padding-top: 10px; border-top: 1px solid #ccc; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name', 'Pesantren') }}</h1>
        <div class="subtitle">DAFTAR PERATURAN ASRAMA</div>
    </div>

    @foreach($regulations as $regulation)
    <div class="regulation">
        <div class="regulation-title">{{ $regulation->name }}</div>
        <div class="regulation-category">Kategori: {{ $regulation->category ? $regulation->category->name : 'Unknown' }} - Status: {{ $regulation->is_active ? 'Aktif' : 'Arsip' }}</div>
        @if($regulation->description)
            <div style="margin-bottom: 10px;"><strong>Deskripsi:</strong> {{ $regulation->description }}</div>
        @endif
        <div class="regulation-content">
            {!! nl2br($regulation->content) !!}
        </div>
    </div>
    @endforeach

    <div class="footer">
        <p>Dibuat pada: {{ date('d F Y H:i') }}</p>
        <p>Jumlah peraturan: {{ count($regulations) }}</p>
    </div>
</body>
</html>
