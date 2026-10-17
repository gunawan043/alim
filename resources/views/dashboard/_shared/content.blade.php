@component('components.breadcrumb')
    @slot('li_1') Dashboard @endslot
    @slot('title') {{ $jabatanLabel }} @endslot
@endcomponent

<div class="dashboard-role" data-role="{{ $roleSlug ?? '' }}" data-jabatan="{{ $jabatanCode }}">

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

    {{-- SECTION + WIDGET (flex auto-fill: setiap baris selalu penuh) --}}
    @foreach ($sections as $section)
        @php
            $sectionWidgets = array_values(array_diff($section['widgets'], ['welcome-banner']));
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
