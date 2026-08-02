<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $regulation->name }} - Peraturan Asrama</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 2cm;
        }
        body {
            font-family: 'Times New Roman', serif;
            line-height: 1.6;
            color: #000;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .title {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .subtitle {
            font-size: 12pt;
            color: #555;
        }
        .content {
            font-size: 12pt;
            text-align: justify;
            margin-top: 20px;
        }
        .section {
            margin-top: 15px;
        }
        .section-title {
            font-weight: bold;
            font-size: 13pt;
            margin-top: 15px;
            margin-bottom: 5px;
            text-decoration: underline;
        }
        .article {
            margin-left: 20px;
            margin-top: 10px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10pt;
            color: #777;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ config('app.name', 'Pesantren') }}</div>
        <div class="subtitle">PERATURAN ASRAMA</div>
    </div>

    <h1 style="text-align: center; font-size: 16pt; margin: 20px 0;">{{ $regulation->name }}</h1>

    <div class="content">
        <div class="section">
            <div class="section-title">Kategori:</div>
            <p>{{ $regulation->category ? $regulation->category->name : 'Tidak ada kategori' }}</div>
        </div>

        <div class="section">
            <div class="section-title">Deskripsi:</div>
            <p>{{ $regulation->description ?: 'Tidak ada deskripsi' }}</div>
        </div>

        <div class="section">
            <div class="section-title">Konten Peraturan:</div>
            <div class="article">
                <p>{!! nl2br($regulation->content) !!}</p>
            </div>
        </div>
    </div>

    <div class="footer no-print">
        <p>Terbitkan pada: {{ date('d F Y', strtotime($regulation->created_at)) }}</p>
        <p><a href="{{ route('user.boarding-regulations.index') }}">Kembali ke Daftar</a></p>
    </div>
</body>
</html>
