<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resep {{ $prescription->prescription_no }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; margin: 0; padding: 24px; font-size: 13px; }
        .sheet { max-width: 760px; margin: 0 auto; }
        .kop { border-bottom: 3px double #111; padding-bottom: 12px; display: flex; gap: 16px; align-items: center; }
        .logo { width: 62px; height: 62px; border: 2px solid #111; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        h1 { margin: 0; font-size: 17px; }
        p { margin: 2px 0; color: #444; }
        h2 { text-align: center; margin: 24px 0 2px; font-size: 16px; letter-spacing: 2px; }
        .nomor { text-align: center; margin-bottom: 20px; color: #555; }
        .patient { display: flex; justify-content: space-between; gap: 20px; margin-bottom: 16px; }
        .patient div { line-height: 1.7; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border-bottom: 1px solid #ccc; padding: 8px 6px; text-align: left; }
        th { background: #f1f1f1; font-size: 12px; }
        .signature { margin-top: 44px; width: 260px; margin-left: auto; text-align: center; }
        .space { height: 60px; }
        .print-btn { position: fixed; top: 16px; right: 16px; padding: 10px 16px; border: 0; background: #198754; color: #fff; border-radius: 6px; cursor: pointer; }
        @media print { .print-btn { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">Cetak</button>

    <div class="sheet">
        <div class="kop">
            <div class="logo">UKS</div>
            <div>
                <h1>UNIT KESEHATAN SEKOLAH (UKS)</h1>
                <p>{{ $prescription->patient?->school?->name ?? 'Sekolah' }}</p>
                <p>Layanan Kesehatan Santri</p>
            </div>
        </div>

        <h2>RESEP</h2>
        <p class="nomor">{{ $prescription->prescription_no }} · {{ $prescription->prescribed_at?->translatedFormat('d F Y H:i') }}</p>

        <div class="patient">
            <div>
                <strong>{{ $prescription->patient?->student?->name ?? '-' }}</strong><br>
                RM: {{ $prescription->patient?->medical_record_no ?? '-' }}<br>
                {{ $prescription->patient?->student?->gender === 'P' ? 'Perempuan' : 'Laki-laki' }}
            </div>
            <div>
                Asrama: {{ $prescription->patient?->student?->activeDormitoryResident?->dormitory?->name ?? '-' }}<br>
                Diagnosis: {{ $prescription->patient?->diagnosis ?? '-' }}<br>
                Status: {{ $prescription->status_label }}
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 34px;">No</th>
                    <th>Nama Obat</th>
                    <th>Dosis</th>
                    <th>Frekuensi</th>
                    <th>Jumlah</th>
                    <th>Aturan Pakai</th>
                </tr>
            </thead>
            <tbody>
                @foreach($prescription->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><strong>{{ $item->medicine_name }}</strong></td>
                        <td>{{ $item->dosage ?? '-' }}</td>
                        <td>{{ $item->frequency ?? '-' }}</td>
                        <td>{{ $item->quantity }} {{ $item->inventory?->unit ?? 'unit' }}</td>
                        <td>{{ $item->instructions ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($prescription->notes)
            <p style="margin-top: 14px;"><strong>Catatan:</strong> {{ $prescription->notes }}</p>
        @endif

        <div class="signature">
            <div>Petugas UKS,</div>
            <div class="space"></div>
            <div><strong>{{ $prescription->prescribedBy?->name ?? '________________' }}</strong></div>
        </div>
    </div>
</body>
</html>
