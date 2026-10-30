@component('components.breadcrumb')
    @slot('li_1') Dashboard @endslot
    @slot('title') {{ $jabatanLabel }} @endslot
@endcomponent

<div class="dashboard-role" data-role="{{ $roleSlug ?? '' }}" data-jabatan="{{ $jabatanCode }}">

    @include('dashboard._shared.partials.header', ['dashboard' => $dashboard])

    {{-- Akses harian khusus GTK Satuan Pendidikan --}}
    @if (($roleSlug ?? '') === 'satuan-pendidikan' || ($roleSlug ?? '') === 'guru' || ($roleSlug ?? '') === 'wali-kelas')
        @php
            $quickActionsView = \Illuminate\Support\Facades\View::exists('dashboard.'.$roleSlug.'.quick-actions')
                ? 'dashboard.'.$roleSlug.'.quick-actions'
                : 'dashboard.satuan-pendidikan.quick-actions';
        @endphp
        @include($quickActionsView, [
            'user' => $user,
            'jabatanCode' => $jabatanCode ?? 'default',
            'tugasTambahan' => $tugasTambahan ?? [],
        ])
    @endif

    {{-- HERO — informasi utama --}}
    @php
        $bannerData = $data['welcome-banner'] ?? null;
        $bannerReady = $bannerData && ($bannerData['message'] ?? null) !== 'Partial not found';
        $bannerView = "{$viewPath}.widgets.welcome-banner";
        if (! View::exists($bannerView) && View::exists('dashboard._shared.widgets.welcome-banner')) {
            $bannerView = 'dashboard._shared.widgets.welcome-banner';
        }
    @endphp
    @if ($bannerReady)
        @include($bannerView, ['data' => $bannerData, 'user' => $user])
    @endif


    {{-- OVERVIEW — indikator utama jabatan --}}
    @if(! empty($dashboard['kpis']))
        @include('dashboard._shared.partials.kpi-cards', ['kpis' => $dashboard['kpis']])
    @endif

    {{-- ANALYSIS — tren utama lintas jabatan --}}
    @include('dashboard._shared.partials.analytics-charts', ['charts' => $dashboard['charts'] ?? [], 'user' => $user])

    {{-- DRILL-DOWN — distribusi & keterisian rombel --}}
    @include('dashboard._shared.partials.distribution', ['dashboard' => $dashboard, 'user' => $user])

    {{-- KALDIK BULAN INI + JADWAL SHALAT --}}
    @include('dashboard._shared.partials.calendar', ['dashboard' => $dashboard])

    {{-- INTERPRETATION — temuan berbasis aturan --}}
    @include('dashboard._shared.partials.findings', ['findings' => $dashboard['findings'] ?? []])

    {{-- ACTION — tindak lanjut sesuai jabatan --}}
    @include('dashboard._shared.partials.actions', ['dashboard' => $dashboard, 'roleSlug' => $roleSlug, 'user' => $user])

    {{-- SECTION + WIDGET (flex auto-fill: setiap baris selalu penuh) --}}
    @foreach ($sections as $section)
        @php
            // Widget lama yang datanya sudah diwakili KPI/grafik baru tidak dirender ulang.
            // Tujuannya supaya satu indikator hanya muncul sekali di halaman.
            $replacedWidgets = [
                'welcome-banner',
                'stat-total-santri', 'stat-total-gtk', 'stat-total-rombel',
                'stat-santri-baru', 'stat-santri-masuk', 'stat-santri-keluar',
                'stat-absensi-santri-hari-ini',
                'chart-tren-kehadiran-santri', 'chart-top-rombel',
                'chart-santri-per-tingkat', 'chart-statistik-santri-per-rombel',
                'chart-gender-santri', 'table-rekap-siswa-tingkat',
            ];
            $sectionWidgets = array_values(array_diff($section['widgets'], $replacedWidgets));
        @endphp

        @continue (empty($sectionWidgets))

        <section class="dash-section">
            <div class="dash-section-header">
                <span class="dash-section-icon">
                    <i class="{{ $section['icon'] ?: 'ri-apps-2-line' }}"></i>
                </span>
                <h6 class="dash-section-title">{{ $section['title'] }}</h6>
                <span class="dash-section-line"></span>
                <span class="dash-section-count">{{ count($sectionWidgets) }} widget</span>
            </div>

            <div class="dash-grid">
                @foreach ($sectionWidgets as $widgetKey)
                    @php
                        $widgetData = $data[$widgetKey] ?? [];
                        $widgetView = "{$viewPath}.widgets.{$widgetKey}";
                        if (! View::exists($widgetView) && View::exists("dashboard._shared.widgets.{$widgetKey}")) {
                            $widgetView = "dashboard._shared.widgets.{$widgetKey}";
                        }
                        if (! View::exists($widgetView)) {
                            $widgetView = match ($widgetData['_type'] ?? null) {
                                'stat'         => 'dashboard._shared.widgets._stat',
                                'chart'        => 'dashboard._shared.widgets._chart',
                                'quick-action' => 'dashboard._shared.widgets._quick-action',
                                'table'        => 'dashboard._shared.widgets._table',
                                default        => $widgetView,
                            };
                        }

                        // Konversi '_col' (Bootstrap) ke span flex 12 kolom
                        $col = $widgetData['_col'] ?? 'col-xl-3 col-md-6';
                        $spanXl = preg_match('/col-xl-(\d+)/', $col, $mx)
                            ? (int) $mx[1]
                            : (str_contains($col, 'col-12') ? 12 : 6);
                        if (preg_match('/col-md-(\d+)/', $col, $mm)) {
                            $spanMd = (int) $mm[1];
                        } elseif (str_contains($col, 'col-12')) {
                            $spanMd = 12;
                        } else {
                            $spanMd = min(12, $spanXl * 2);
                        }
                    @endphp

                    <div class="dash-span" data-widget="{{ $widgetKey }}" style="--span-xl: {{ $spanXl }}; --span-md: {{ $spanMd }};">
                        @if (! empty($widgetData['error']))
                            <div class="alert alert-danger small mb-0">
                                <i class="ri-error-warning-line me-1"></i>
                                Widget <code>{{ $widgetKey }}</code> gagal dimuat.
                            </div>
                        @elseif (View::exists($widgetView))
                            @include($widgetView, ['data' => $widgetData, 'user' => $user])
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
