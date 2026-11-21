<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
    (function () {
        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // Tema grafik: satu aksen navy, sisanya ramp netral.
        if (window.ApexCharts) {
            window.Apex = {
                chart: { fontFamily: 'inherit', toolbar: { show: false }, background: 'transparent' },
                colors: ['#00416c', '#8b949f', '#ffae01'],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2 },
                grid: { borderColor: 'rgba(23, 33, 43, .08)' },
                tooltip: { theme: 'light' },
            };
        }

        // Warna teks grafik mengikuti tema aktif (label sumbu & angka tengah
        // donut digambar di kanvas SVG, jadi tidak ikut variabel CSS).
        window.dashInk = function () {
            return document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#e9edf2' : '#17212b';
        };

        window.dashMutedInk = function () {
            return document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#a6afba' : '#5a6472';
        };

        // ---------------------------------------------------------------
        // Angka: 12345 -> 12.345
        // ---------------------------------------------------------------
        window.dashFormat = function (value) {
            return Math.round(value).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        };

        // ---------------------------------------------------------------
        // 1. Grafik dibuat saat kartunya masuk layar.
        //    Kalau dirender saat halaman dimuat, animasinya sudah selesai
        //    sebelum pembaca menggulir ke sana — jadi tidak pernah terlihat.
        // ---------------------------------------------------------------
        var chartObserver = 'IntersectionObserver' in window
            ? new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) { return; }
                    var el = entry.target;
                    chartObserver.unobserve(el);

                    var factory = el.__dashChart;
                    if (typeof factory === 'function') {
                        el.__dashChart = null;
                        factory();
                    }
                });
            }, { rootMargin: '0px 0px -60px 0px', threshold: 0.15 })
            : null;

        window.dashChart = function (id, options) {
            var el = document.getElementById(id);
            if (!el || !window.ApexCharts) { return; }

            var build = function () {
                var chart = new ApexCharts(el, options);
                chart.render();
                el.__dashChartInstance = chart;
            };

            if (!chartObserver) {
                build();
                return;
            }

            el.__dashChart = build;
            chartObserver.observe(el);
        };

        // ---------------------------------------------------------------
        // 2. Angka KPI naik dari 0 ke nilai akhir.
        // ---------------------------------------------------------------
        function runCount(el) {
            var target = parseFloat(el.getAttribute('data-dash-count'));
            if (!isFinite(target)) { return; }

            if (reduceMotion) {
                el.textContent = window.dashFormat(target);
                return;
            }

            var duration = 620;
            var startedAt = null;

            function frame(now) {
                if (startedAt === null) { startedAt = now; }
                var progress = Math.min((now - startedAt) / duration, 1);
                // ease-out: cepat di awal, melambat di akhir.
                var eased = 1 - Math.pow(1 - progress, 3);
                el.textContent = window.dashFormat(target * eased);
                if (progress < 1) { requestAnimationFrame(frame); }
            }

            requestAnimationFrame(frame);
        }

        // ---------------------------------------------------------------
        // 3. Bar keterisian tumbuh dari kiri memakai scaleX (bukan width,
        //    supaya tidak memicu layout ulang di perangkat lemah).
        // ---------------------------------------------------------------
        function runFill(row) {
            var bar = row.querySelector('[data-dash-fill]');
            if (!bar) { return; }

            var value = Math.max(0, Math.min(100, parseFloat(bar.getAttribute('data-dash-fill')) || 0));
            var index = parseInt(row.getAttribute('data-fill-index') || '0', 10);

            bar.style.transitionDelay = (reduceMotion ? 0 : Math.min(index, 14) * 35) + 'ms';
            bar.style.transform = 'scaleX(' + (value / 100) + ')';
        }

        var fillObserver = 'IntersectionObserver' in window
            ? new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) { return; }
                    fillObserver.unobserve(entry.target);
                    runFill(entry.target);
                });
            }, { rootMargin: '0px 0px -40px 0px', threshold: 0.2 })
            : null;

        // ---------------------------------------------------------------
        // 4. Satu animasi masuk per blok halaman, dari atas ke bawah.
        // ---------------------------------------------------------------
        document.addEventListener('DOMContentLoaded', function () {
            var blocks = Array.prototype.slice.call(document.querySelectorAll('.dashboard-role > *'));

            if (reduceMotion) {
                blocks.forEach(function (el) { el.classList.add('dash-reveal'); });
                document.querySelectorAll('[data-dash-count]').forEach(runCount);
                document.querySelectorAll('[data-fill-row]').forEach(runFill);
                return;
            }

            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) { return; }

                    var el = entry.target;
                    var index = blocks.indexOf(el);
                    el.style.animationDelay = Math.min(index, 7) * 40 + 'ms';
                    el.classList.add('dash-reveal');
                    el.querySelectorAll('[data-dash-count]').forEach(runCount);

                    observer.unobserve(el);
                });
            }, { rootMargin: '0px 0px -32px 0px', threshold: 0.05 });

            blocks.forEach(function (el) { observer.observe(el); });

            document.querySelectorAll('[data-fill-row]').forEach(function (row, index) {
                row.setAttribute('data-fill-index', index);
                if (fillObserver) {
                    fillObserver.observe(row);
                } else {
                    runFill(row);
                }
            });
        });
    })();
</script>
