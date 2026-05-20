<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QR Labels - {{ date('d/m/Y') }}</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Arial', sans-serif; background: white; color: #222; }
.header { text-align: center; border-bottom: 2px solid #333; padding: 16px 0 12px; margin-bottom: 16px; }
.header h2 { font-size: 16px; font-weight: 700; letter-spacing: 0.5px; }
.header p { font-size: 11px; color: #666; margin-top: 2px; }
.grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; padding: 0 16px 16px; }
.label { border: 1px solid #333; border-radius: 6px; padding: 10px; text-align: center; page-break-inside: avoid; }
.label img { width: 90px; height: 90px; margin: 0 auto; display: block; }
.label .name { font-size: 11px; font-weight: 700; margin-top: 6px; line-height: 1.3; }
.label .code { font-size: 9px; color: #555; margin-top: 2px; }
.label .room { font-size: 9px; color: #888; }
.label .school { font-size: 8px; color: #aaa; border-top: 1px dashed #ddd; margin-top: 4px; padding-top: 4px; }
@page { size: A4; margin: 10mm; }
@media print { .grid { padding: 0; } }
</style>
</head>
<body>
<div class="header">
    <h2>DAFTAR QR CODE ASET</h2>
    <p>Dicetak: {{ date('d F Y, H:i') }} | Total: {{ $assets->count() }} aset</p>
</div>
<div class="grid">
    @foreach($assets as $asset)
    <div class="label">
        <img src="data:image/png;base64,{{ $qrData[$asset->id] ?? '' }}" alt="QR">
        <div class="name">{{ Str::limit($asset->asset_name, 40) }}</div>
        <div class="code">{{ $asset->asset_code ?? '-' }}</div>
        <div class="room">{{ $asset->room?->room_name ?? '-' }}</div>
        @if($asset->room?->school)
        <div class="school">{{ $asset->room->school->name }}</div>
        @endif
    </div>
    @endforeach
</div>
</body>
</html>