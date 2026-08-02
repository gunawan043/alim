<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Asset Passport — {{ $asset->asset_name }}</title>
<style>
body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #222; }
h1 { font-size: 18px; margin-bottom: 4px; }
h2 { font-size: 14px; margin-top: 14px; border-bottom: 1px solid #ccc; padding-bottom: 2px; }
table { width: 100%; border-collapse: collapse; margin-top: 6px; }
th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; vertical-align: top; }
th { background: #f5f5f5; }
.badge { padding: 2px 6px; border-radius: 3px; font-size: 9px; display: inline-block; }
.badge-v2 { background: #17a2b8; color: #fff; }
.muted { color: #666; }
.text-end { text-align: right; }
.grid { display: table; width: 100%; }
.col { display: table-cell; width: 50%; padding-right: 8px; }
</style>
</head>
<body>

<h1>
    {{ $asset->asset_name }}
    @if($version === '2')<span class="badge badge-v2">Passport 2.0</span>@endif
</h1>
<p class="muted">{{ $asset->asset_code ?? $asset->id }} — generated {{ $generated_at }}</p>

<h2>Identity</h2>
<table>
    <tr><th>Code</th><td>{{ $asset->asset_code }}</td><th>Category</th><td>{{ $asset->category?->name ?? '—' }}</td></tr>
    <tr><th>Status</th><td>{{ $asset->status }}</td><th>Condition</th><td>{{ $asset->condition }}</td></tr>
    <tr><th>Room</th><td>{{ $asset->room?->room_name ?? '—' }}</td><th>Building</th><td>{{ $asset->room?->building?->building_name ?? '—' }}</td></tr>
    <tr><th>PIC</th><td>{{ $asset->creator?->name ?? '—' }}</td><th>Work Unit</th><td>{{ $asset->workUnit?->name ?? '—' }}</td></tr>
</table>

<h2>Health</h2>
<table>
    <tr><th>Score</th><td>{{ $passport['health']['score'] ?? '—' }}</td><th>Status</th><td>{{ $passport['health']['status'] ?? '—' }}</td></tr>
</table>

@if(isset($passport['criticality']))
<h2>Criticality</h2>
<table>
    <tr><th>Level</th><td>{{ $passport['criticality']['level'] ?? '—' }}</td><th>Reason</th><td>{{ $passport['criticality']['reason'] ?? '—' }}</td></tr>
</table>
@endif

<h2>Warranty</h2>
<table>
    <tr><th>Vendor</th><td>{{ $passport['warranty']['vendor'] ?? '—' }}</td><th>End</th><td>{{ $passport['warranty']['end_date'] ?? '—' }}</td><th>Status</th><td>{{ $passport['warranty']['status'] ?? '—' }}</td></tr>
</table>

<h2>Financial</h2>
<table>
    <tr><th>Acquisition</th><td class="text-end">Rp {{ number_format($passport['financial']['acquisition_price'] ?? 0, 0, ',', '.') }}</td>
        <th>Current Value</th><td class="text-end">Rp {{ number_format($passport['financial']['current_value'] ?? 0, 0, ',', '.') }}</td></tr>
    <tr><th>Depreciation</th><td class="text-end">Rp {{ number_format($passport['financial']['depreciation'] ?? 0, 0, ',', '.') }}</td>
        <th>Useful Life</th><td>{{ $passport['financial']['useful_life_years'] ?? '—' }} tahun</td></tr>
</table>

@if(isset($passport['tco']))
<h2>Total Cost of Ownership <span class="badge badge-v2">v2</span></h2>
<table>
    <tr><th>Acquisition Cost</th><td class="text-end">Rp {{ number_format($passport['tco']['acquisition_cost_total'] ?? 0, 0, ',', '.') }}</td></tr>
    <tr><th>Maintenance Cost</th><td class="text-end">Rp {{ number_format($passport['tco']['maintenance_cost_total'] ?? 0, 0, ',', '.') }}</td></tr>
    <tr><th>Repair Cost</th><td class="text-end">Rp {{ number_format($passport['tco']['repair_cost_total'] ?? 0, 0, ',', '.') }}</td></tr>
    <tr><th>Downtime Cost</th><td class="text-end">Rp {{ number_format($passport['tco']['downtime_cost_total'] ?? 0, 0, ',', '.') }}</td></tr>
    <tr><th>TCO Total</th><td class="text-end"><strong>Rp {{ number_format($passport['tco']['tco_total'] ?? 0, 0, ',', '.') }}</strong></td></tr>
    <tr><th>Cost / Month</th><td class="text-end">Rp {{ number_format($passport['tco']['tco_per_month'] ?? 0, 0, ',', '.') }}</td></tr>
</table>
@endif

@if(isset($passport['repair_vs_replace']))
<h2>Repair vs Replace <span class="badge badge-v2">v2</span></h2>
<table>
    <tr><th>Recommendation</th><td><strong>{{ $passport['repair_vs_replace']['recommendation'] }}</strong></td></tr>
    <tr><th>Score</th><td>{{ $passport['repair_vs_replace']['score'] }}/100</td></tr>
    @if(!empty($passport['repair_vs_replace']['rationale']))
    <tr><th>Rationale</th><td>
        <ul style="margin: 0; padding-left: 16px;">
            @foreach($passport['repair_vs_replace']['rationale'] as $r)
                <li>{{ $r }}</li>
            @endforeach
        </ul>
    </td></tr>
    @endif
</table>
@endif

@if(isset($passport['predictive']) && $passport['predictive'])
<h2>Predictive Maintenance <span class="badge badge-v2">v2</span></h2>
<table>
    <tr><th>MTBF</th><td>{{ $passport['predictive']['mtbf_days'] ?? '—' }} hari</td>
        <th>MTTR</th><td>{{ $passport['predictive']['mttr_days'] ?? '—' }} hari</td></tr>
    <tr><th>Trend</th><td>{{ $passport['predictive']['health_trend'] ?? '—' }}</td>
        <th>Repairs/Month</th><td>{{ $passport['predictive']['repairs_per_month'] ?? '—' }}</td></tr>
    @if(!empty($passport['predictive']['recommendation']))
    <tr><th>Recommendation</th><td colspan="3">{{ $passport['predictive']['recommendation'] }}</td></tr>
    @endif
</table>
@endif

<h2>Cost Summary</h2>
<table>
    <tr><th>Total Repair</th><td class="text-end">Rp {{ number_format($passport['costs']['total_repair'] ?? 0, 0, ',', '.') }}</td>
        <th>Total Maintenance</th><td class="text-end">Rp {{ number_format($passport['costs']['total_maintenance'] ?? 0, 0, ',', '.') }}</td></tr>
</table>

<h2>Recent Lifecycle</h2>
@if(!empty($passport['history']) && count($passport['history']) > 0)
<table>
    <tr><th>Date</th><th>Type</th><th>Description</th></tr>
    @foreach(array_slice($passport['history'], 0, 10) as $h)
        <tr><td>{{ $h['date'] ?? '' }}</td><td>{{ $h['type'] ?? '' }}</td><td>{{ $h['description'] ?? '' }}</td></tr>
    @endforeach
</table>
@else
<p class="muted">No history available.</p>
@endif

<p class="muted" style="margin-top: 18px;">
    Generated by ALIM Sarpras Asset Intelligence — {{ $generated_at }}
</p>
</body>
</html>