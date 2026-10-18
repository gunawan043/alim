@extends('layouts.master')
@section('title') Prediksi Kerusakan @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Prediksi Kerusakan
    @endcomponent

    @php
        $riskCounts = collect($predictions)->countBy('risk_level');
        $critical = (int) ($riskCounts['CRITICAL'] ?? 0);
        $high = (int) ($riskCounts['HIGH'] ?? 0);
        $tracked = count($predictions);
    @endphp

    <x-sarpras-stats :items="[
        ['label'=>'Aset Dipantau','value'=>$tracked,'tone'=>'primary','icon'=>'ri-radar-line'],
        ['label'=>'Risiko Kritis','value'=>$critical,'tone'=>'danger','icon'=>'ri-alarm-warning-line'],
        ['label'=>'Risiko Tinggi','value'=>$high,'tone'=>'warning','icon'=>'ri-error-warning-line'],
        ['label'=>'Risiko Rendah','value'=>(int)($riskCounts['LOW'] ?? 0),'tone'=>'success','icon'=>'ri-shield-check-line'],
    ]" />

    <x-sarpras-filter-card :action="route('sarpras.predictive.index')" :reset="route('sarpras.predictive.index')">
        <div class="col-md-3"><label class="form-label small fw-semibold">Level Risiko</label><select name="level" class="form-select"><option value="">Semua Risiko</option>@foreach(['LOW','MEDIUM','HIGH','CRITICAL'] as $lvl)<option value="{{ $lvl }}" @selected($filter === $lvl)>{{ $lvl }}</option>@endforeach</select></div>
    </x-sarpras-filter-card>

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0"><i class="ri-radar-line me-2 text-primary"></i>Prediksi Kerusakan Aset</h5><p class="text-muted small mb-0">MTBF, MTTR, dan tren untuk mencegah downtime.</p></div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Kode</th><th>Nama Aset</th><th>Risiko</th><th class="text-center">MTBF</th><th class="text-center">MTTR</th><th class="text-center">Perbaikan</th><th class="text-center">Tren</th><th class="text-center">Prediksi Gagal</th></tr></thead><tbody>
            @forelse($predictions as $p)
                @php
                    $color = ['CRITICAL'=>'danger','HIGH'=>'warning','MEDIUM'=>'info'][$p['risk_level']] ?? 'success';
                    $trendIcon = ['deteriorating'=>'📉','improving'=>'📈','stable'=>'➡️'][$p['trend']] ?? '❔';
                @endphp
                <tr>
                    <td><code>{{ $p['asset_code'] }}</code></td>
                    <td><a href="{{ route('sarpras.predictive.show', ['asset' => $p['asset_id']]) }}" class="fw-medium link-primary">{{ $p['asset_name'] }}</a></td>
                    <td><span class="badge bg-{{ $color }}-subtle text-{{ $color }}">{{ $p['risk_level'] }}</span></td>
                    <td class="text-center">{{ round($p['mtbf_days']) }} hari</td>
                    <td class="text-center">{{ round($p['mttr_days']) }} hari</td>
                    <td class="text-center">{{ $p['repair_count'] }}</td>
                    <td class="text-center">{{ $trendIcon }} {{ $p['trend'] }}</td>
                    <td class="text-center">{{ $p['next_predicted_failure'] ?? '—' }}@if($p['days_until_failure'] !== null && $p['days_until_failure'] >= 0)<small class="d-block text-muted">{{ $p['days_until_failure'] }} hari lagi</small>@endif</td>
                </tr>
            @empty
                <x-empty-state colspan="8" title="Belum ada prediksi" message="Prediksi muncul setelah aset memiliki riwayat perbaikan." />
            @endforelse
        </tbody></table></div></div>
    </div>
@endsection
