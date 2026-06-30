<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji - {{ $payroll->gtk?->nama ?? 'GTK' }} - {{ str_pad($payroll->bulan, 2, '0', STR_PAD_LEFT) }}/{{ $payroll->tahun }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1f2937; }
        .header { text-align: center; border-bottom: 2px solid #1f2937; padding-bottom: 8px; margin-bottom: 16px; }
        .header h1 { margin: 0; font-size: 16px; }
        .header small { color: #6b7280; }
        .meta { margin-bottom: 12px; }
        .meta table { width: 100%; }
        .meta td { padding: 2px 4px; }
        .meta .label { color: #6b7280; width: 120px; }
        table.detail { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.detail th, table.detail td { border: 1px solid #d1d5db; padding: 6px 8px; }
        table.detail th { background: #f3f4f6; text-align: left; }
        table.detail td.amount { text-align: right; }
        .total-row td { font-weight: bold; background: #fef3c7; }
        .signature { margin-top: 30px; width: 100%; }
        .signature td { text-align: center; padding-top: 60px; }
        .footer { margin-top: 18px; font-size: 9px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SLIP GAJI GTK</h1>
        <small>Pondok Pesantren Al-Imam</small>
    </div>

    <div class="meta">
        <table>
            <tr>
                <td class="label">Nama GTK</td><td>: {{ $payroll->gtk?->nama ?? '-' }}</td>
                <td class="label">Periode</td><td>: {{ str_pad($payroll->bulan, 2, '0', STR_PAD_LEFT) }}/{{ $payroll->tahun }}</td>
            </tr>
            <tr>
                <td class="label">No. Induk</td><td>: {{ $payroll->gtk?->nik ?? '-' }}</td>
                <td class="label">Status</td><td>: {{ ucwords(str_replace('_', ' ', $payroll->status)) }}</td>
            </tr>
        </table>
    </div>

    <table class="detail">
        <thead>
            <tr><th style="width:60%">Komponen</th><th style="width:40%" class="amount">Nominal (Rp)</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Gaji Pokok</td>
                <td class="amount">{{ number_format((float) $payroll->gaji_pokok, 0, ',', '.') }}</td>
            </tr>
            @if(is_array($payroll->detail_tunjangan) && count($payroll->detail_tunjangan) > 0)
                <tr><td colspan="2" style="background:#f9fafb;font-weight:600">Tunjangan</td></tr>
                @foreach($payroll->detail_tunjangan as $row)
                    <tr>
                        <td style="padding-left:18px">- {{ $row['jenis'] ?? 'Tunjangan' }}</td>
                        <td class="amount">{{ number_format((float) ($row['nominal'] ?? 0), 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td style="padding-left:18px;font-weight:600">Subtotal Tunjangan</td>
                    <td class="amount">{{ number_format((float) $payroll->total_tunjangan, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr>
                <td><strong>Total Pendapatan</strong></td>
                <td class="amount"><strong>{{ number_format((float) ($payroll->gaji_pokok + $payroll->total_tunjangan), 0, ',', '.') }}</strong></td>
            </tr>
            @if(is_array($payroll->detail_potongan) && count($payroll->detail_potongan) > 0)
                <tr><td colspan="2" style="background:#f9fafb;font-weight:600">Potongan</td></tr>
                @foreach($payroll->detail_potongan as $row)
                    <tr>
                        <td style="padding-left:18px">- {{ $row['jenis'] ?? 'Potongan' }}</td>
                        <td class="amount">{{ number_format((float) ($row['nominal'] ?? 0), 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td style="padding-left:18px;font-weight:600">Subtotal Potongan</td>
                    <td class="amount">{{ number_format((float) $payroll->total_potongan, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td><strong>GAJI BERSIH (Take Home Pay)</strong></td>
                <td class="amount"><strong>Rp {{ number_format((float) $payroll->gaji_bersih, 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>

    @if(!empty($payroll->catatan))
        <p style="margin-top:14px"><strong>Catatan:</strong> {{ $payroll->catatan }}</p>
    @endif

    <table class="signature">
        <tr>
            <td>GTK Penerima</td>
            <td>Pembuat Slip</td>
        </tr>
        <tr>
            <td><strong>{{ $payroll->gtk?->nama ?? '________________' }}</strong></td>
            <td>Personalia</td>
        </tr>
    </table>

    <div class="footer">
        Dokumen ini dicetak otomatis oleh sistem ALIM pada {{ now()->format('d M Y H:i') }}.
    </div>
</body>
</html>
