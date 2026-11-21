{{--
    LAPORAN — SKRIP BERSAMA
    =======================
    Semua grafik laporan memakai satu jalur: dibuat saat kartunya masuk
    viewport, supaya animasi ApexCharts benar-benar terlihat, bukan selesai
    sebelum halaman digulir.

    Halaman yang punya grafik memanggil partial ini di dalam @section('script')
    sebelum skrip halamannya sendiri, karena @yield('script') dirender lebih
    dulu daripada @stack('scripts').
--}}
<script>
    (function () {
        if (window.lapChart) { return; }

        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        window.lapInk = function () {
            return document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#e9edf2' : '#17212b';
        };

        window.lapMuted = function () {
            return document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#7b848f' : '#8b949f';
        };

        window.lapSecondary = function () {
            return document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#a6afba' : '#5a6472';
        };

        window.lapGrid = function () {
            return document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'rgba(255,255,255,.09)' : 'rgba(23,33,43,.08)';
        };

        window.lapFormat = function (value) {
            if (value === null || value === undefined || value === '') { return '—'; }
            var n = Number(value);
            if (!isFinite(n)) { return String(value); }
            return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        };

        window.lapTooltip = function (title, rows) {
            var html = '<div class="lap-tooltip">';
            if (title) { html += '<div class="lap-tooltip-title">' + title + '</div>'; }
            (rows || []).forEach(function (row) {
                html += '<div class="lap-tooltip-row"><span>' + row[0] + '</span><b>' + row[1] + '</b></div>';
            });
            return html + '</div>';
        };

        var observer = ('IntersectionObserver' in window) ? new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) { return; }
                var el = entry.target;
                observer.unobserve(el);
                var build = el.__lapChart;
                if (typeof build === 'function') {
                    el.__lapChart = null;
                    build();
                }
            });
        }, { rootMargin: '0px 0px -40px 0px', threshold: 0.1 }) : null;

        var withDefaults = function (options) {
            options = options || {};
            var chart = Object.assign({
                fontFamily: 'inherit',
                background: 'transparent',
                toolbar: { show: false },
                animations: reduce ? { enabled: false } : {
                    enabled: true,
                    easing: 'easeout',
                    speed: 700,
                    animateGradually: { enabled: true, delay: 60 },
                    dynamicAnimation: { enabled: true, speed: 350 }
                }
            }, options.chart || {});

            return Object.assign({
                grid: {
                    borderColor: window.lapGrid(),
                    strokeDashArray: 0,
                    padding: { top: 4, right: 8, bottom: 0, left: 8 }
                },
                dataLabels: { enabled: false },
                legend: { show: false },
                tooltip: { theme: 'light' },
                noData: { text: '', align: 'center', verticalAlign: 'middle' }
            }, options, { chart: chart });
        };

        window.lapChart = function (id, options) {
            var el = document.getElementById(id);
            if (!el || !window.ApexCharts) { return; }

            var config = withDefaults(options);
            var build = function () {
                var chart = new ApexCharts(el, config);
                chart.render();
                el.__lapChartInstance = chart;
            };

            if (!observer || reduce) { build(); return; }
            el.__lapChart = build;
            observer.observe(el);
        };

        window.lapCount = function (root) {
            var scope = root || document;
            var nodes = scope.querySelectorAll('[data-lap-count]');
            if (!nodes.length) { return; }
            if (reduce) {
                nodes.forEach(function (el) { el.textContent = lapFormat(el.getAttribute('data-lap-count')); });
                return;
            }
            nodes.forEach(function (el) {
                var target = Number(el.getAttribute('data-lap-count'));
                if (!isFinite(target)) { return; }
                var start = null;
                var duration = 560;
                var step = function (ts) {
                    if (start === null) { start = ts; }
                    var p = Math.min(1, (ts - start) / duration);
                    var eased = 1 - Math.pow(1 - p, 3);
                    el.textContent = lapFormat(target * eased);
                    if (p < 1) { requestAnimationFrame(step); }
                    else { el.textContent = lapFormat(target); }
                };
                requestAnimationFrame(step);
            });
        };

        window.lapBars = function (root) {
            var scope = root || document;
            var nodes = scope.querySelectorAll('[data-lap-bar]');
            if (!nodes.length) { return; }
            var paint = function (el) {
                el.style.transform = 'scaleX(' + (el.getAttribute('data-lap-bar') || 0) + ')';
            };
            if (reduce || !('IntersectionObserver' in window)) {
                nodes.forEach(paint);
                return;
            }
            var barObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) { return; }
                    barObserver.unobserve(entry.target);
                    paint(entry.target);
                });
            }, { threshold: 0.2 });
            nodes.forEach(function (el) { barObserver.observe(el); });
        };

        window.lapBoot = function () {
            window.lapCount();
            window.lapBars();
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', window.lapBoot);
        } else {
            window.lapBoot();
        }
    })();
</script>
