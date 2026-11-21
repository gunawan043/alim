{{--
    DASHBOARD — SISTEM VISUAL BERSAMA
    =================================
    Satu ramp netral (abu hangat), satu aksen institusi (navy + emas dari
    halaman login), satu kurva gerak. Semua warna dekoratif dihapus supaya
    angka dan isi yang menonjol, bukan chrome-nya.

    Kelas di bawah dipakai bersama oleh ±90 widget. Jangan ganti nama kelas
    tanpa menyisir seluruh folder dashboard/_shared/widgets.
--}}
<style>
    /* ---------- TOKEN ---------- */
    .dashboard-role {
        --dash-ink: #17212b;
        --dash-ink-2: #5a6472;
        --dash-ink-3: #8b949f;
        --dash-surface: #ffffff;
        --dash-surface-2: #f6f7f8;
        --dash-line: rgba(23, 33, 43, .08);
        --dash-line-2: rgba(23, 33, 43, .16);
        --dash-navy: #00416c;
        --dash-navy-2: #005981;
        --dash-navy-soft: rgba(0, 65, 108, .06);
        --dash-gold: #ffae01;
        --dash-gold-ink: #8a5a00;
        --dash-ok: #2f7d4f;
        --dash-warn: #9a6a00;
        --dash-bad: #a4462f;
        --dash-radius: 14px;
        --dash-radius-sm: 10px;
        --dash-ease: cubic-bezier(.22, .61, .36, 1);
        --dash-shadow: 0 1px 2px rgba(23, 33, 43, .05);
        --dash-shadow-hover: 0 4px 12px rgba(23, 33, 43, .07);
        color: var(--dash-ink);
    }

    [data-bs-theme="dark"] .dashboard-role {
        --dash-ink: #e9edf2;
        --dash-ink-2: #a6afba;
        --dash-ink-3: #7b848f;
        --dash-surface: #1b222b;
        --dash-surface-2: #222a34;
        --dash-line: rgba(255, 255, 255, .09);
        --dash-line-2: rgba(255, 255, 255, .18);
        --dash-navy: #0a4a75;
        --dash-navy-2: #0a5b8c;
        --dash-navy-soft: rgba(90, 160, 210, .12);
        --dash-gold-ink: #ffc64d;
        --dash-ok: #6bbf8b;
        --dash-warn: #e0b04a;
        --dash-bad: #e0846c;
        --dash-shadow: none;
        --dash-shadow-hover: none;
    }

    /* ---------- GRID WIDGET (flex, tiap baris selalu penuh) ---------- */
    .dash-grid {
        display: flex;
        flex-wrap: wrap;
        align-items: stretch;
        gap: 16px;
    }

    .dash-grid > .dash-span {
        flex: 0 0 100%;
        max-width: 100%;
        min-width: 0;
    }

    @media (min-width: 768px) {
        .dash-grid > .dash-span {
            flex-grow: var(--span-md, 12);
            flex-shrink: 1;
            flex-basis: calc((100% - 11rem) / 12 * var(--span-md, 12) + (var(--span-md, 12) - 1) * 1rem);
        }
    }

    @media (min-width: 1200px) {
        .dash-grid > .dash-span {
            flex-grow: var(--span-xl, 12);
            flex-shrink: 1;
            flex-basis: calc((100% - 11rem) / 12 * var(--span-xl, 12) + (var(--span-xl, 12) - 1) * 1rem);
        }
    }

    /* Section berisi satu widget: jangan melebar penuh, cukup selebar
       kolomnya sendiri supaya kartu tidak berisi tiga kata di ruang kosong. */
    .dash-grid-single > .dash-span {
        flex-grow: 0;
    }

    /* ---------- KARTU ---------- */
    .dashboard-role .card {
        background: var(--dash-surface);
        border: 1px solid var(--dash-line);
        border-radius: var(--dash-radius);
        box-shadow: var(--dash-shadow);
        height: 100%;
        transition: border-color .16s var(--dash-ease), box-shadow .16s var(--dash-ease);
    }

    .dashboard-role .card-body {
        padding: 20px;
    }

    .dashboard-role .card-header {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 20px;
        min-height: 48px;
        background: transparent;
        border-bottom: 1px solid var(--dash-line);
    }

    .dashboard-role .card-header .card-title {
        flex: 1 1 auto;
        margin: 0;
        font-size: 14px;
        font-weight: 600;
        letter-spacing: 0;
        line-height: 1.4;
        color: var(--dash-ink);
    }

    /* Ikon di judul kartu hanya mengulang kata di sebelahnya.
       Semua widget memakai pola yang sama, jadi dimatikan terpusat. */
    .dashboard-role .card-header .card-title > i {
        display: none;
    }

    .dashboard-role .card-footer {
        background: transparent;
        border-top: 1px solid var(--dash-line);
    }

    /* Kartu yang bisa diklik: perubahan permukaan, bukan lompatan. */
    .dashboard-role a.card:hover,
    .dashboard-role .card-animate:hover {
        border-color: var(--dash-line-2);
        box-shadow: var(--dash-shadow-hover);
    }

    .dashboard-role a.card:focus-visible,
    .dashboard-role .card:focus-within {
        outline: 2px solid var(--dash-navy-2);
        outline-offset: 2px;
    }

    /* ---------- SECTION ---------- */
    .dash-section {
        margin-bottom: 24px;
    }

    .dash-section-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px;
    }

    .dash-section-title {
        margin: 0;
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 0;
        text-transform: none;
        color: var(--dash-ink-2);
        white-space: nowrap;
    }

    .dash-section-line {
        flex: 1;
        height: 1px;
        background: var(--dash-line);
    }

    .dash-section-count {
        font-size: 11px;
        font-weight: 500;
        color: var(--dash-ink-3);
        background: none;
        border-radius: 0;
        padding: 0;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }

    /* ---------- BANNER SAMBUTAN ---------- */
    .welcome-banner {
        position: relative;
        overflow: hidden;
        border-radius: var(--dash-radius);
        padding: 20px 24px 24px;
        color: #fff;
        background: var(--dash-navy);
    }

    /* Satu-satunya emas di bagian atas halaman: garis tipis penanda institusi. */
    .welcome-banner::after {
        content: '';
        position: absolute;
        inset: auto 0 0 0;
        height: 3px;
        background: var(--dash-gold);
    }

    .welcome-banner .banner-eyebrow {
        display: inline-flex;
        align-items: center;
        font-size: 12px;
        font-weight: 500;
        letter-spacing: 0;
        text-transform: none;
        color: rgba(255, 255, 255, .72);
        background: none;
        border-radius: 0;
        padding: 0;
        margin-bottom: 4px;
    }

    .welcome-banner .banner-title {
        color: #fff;
        font-weight: 600;
        font-size: 22px;
        letter-spacing: -.01em;
        line-height: 1.3;
        margin: 0 0 8px;
    }

    .welcome-banner .banner-name {
        font-weight: 600;
    }

    .welcome-banner .banner-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px 14px;
        color: rgba(255, 255, 255, .78);
        font-size: 13px;
    }

    .welcome-banner .banner-chip {
        display: inline-flex;
        align-items: center;
        font-size: 12px;
        font-weight: 500;
        color: rgba(255, 255, 255, .82);
        background: none;
        border-radius: 0;
        padding: 0;
    }

    .welcome-banner .banner-icon-wrap {
        display: none;
    }

    /* Ringkasan hari ini di sisi kanan banner — isi nyata, bukan hiasan. */
    .banner-today {
        border-left: 1px solid rgba(255, 255, 255, .18);
        padding-left: 20px;
        min-width: 220px;
    }

    .banner-today-label {
        font-size: 11px;
        font-weight: 500;
        color: rgba(255, 255, 255, .6);
        margin-bottom: 6px;
    }

    .banner-today-item {
        display: flex;
        align-items: baseline;
        gap: 8px;
        font-size: 13px;
        color: rgba(255, 255, 255, .9);
        padding: 2px 0;
    }

    .banner-today-item .time {
        font-variant-numeric: tabular-nums;
        font-weight: 600;
        color: var(--dash-gold);
        min-width: 42px;
    }

    .banner-today-empty {
        font-size: 13px;
        color: rgba(255, 255, 255, .6);
    }

    @media (max-width: 991.98px) {
        .welcome-banner { padding: 20px; }
        .welcome-banner .banner-title { font-size: 19px; }
        .banner-today {
            border-left: 0;
            border-top: 1px solid rgba(255, 255, 255, .18);
            padding-left: 0;
            padding-top: 14px;
            margin-top: 4px;
            width: 100%;
            min-width: 0;
        }
    }

    /* ---------- KPI ---------- */
    .dash-kpi-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 16px;
    }

    /* Empat kartu per baris. Tidak memakai flex-grow: kartu terakhir di baris
       terakhir tetap selebar kartu lain, tidak melebar dengan ruang kosong. */
    .dash-kpi-cell {
        flex: 0 1 100%;
    }

    @media (min-width: 576px) {
        .dash-kpi-cell { flex-basis: calc(50% - 6px); }
    }

    @media (min-width: 992px) {
        .dash-kpi-cell { flex-basis: calc(33.333% - 8px); }
    }

    @media (min-width: 1200px) {
        .dash-kpi-cell { flex-basis: calc(25% - 9px); }
    }

    .dash-kpi {
        display: block;
        height: 100%;
        padding: 16px 20px;
        text-decoration: none;
        color: inherit;
    }

    .dash-kpi-label {
        margin: 0 0 8px;
        font-size: 12px;
        font-weight: 500;
        letter-spacing: 0;
        text-transform: none;
        color: var(--dash-ink-2);
    }

    .dash-kpi-value {
        margin: 0;
        font-size: 28px;
        line-height: 1.1;
        font-weight: 600;
        letter-spacing: -.02em;
        font-variant-numeric: tabular-nums;
        color: var(--dash-ink);
    }

    .dash-kpi-value .dash-kpi-unit {
        font-size: 14px;
        font-weight: 500;
        color: var(--dash-ink-3);
        margin-left: 2px;
    }

    .dash-kpi-note {
        margin: 6px 0 0;
        font-size: 12px;
        line-height: 1.4;
        color: var(--dash-ink-3);
    }

    .dash-kpi-note.is-warning { color: var(--dash-warn); }

    .dash-kpi-delta {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        font-size: 12px;
        font-weight: 500;
        font-variant-numeric: tabular-nums;
    }

    .dash-kpi-delta.is-up { color: var(--dash-ok); }
    .dash-kpi-delta.is-down { color: var(--dash-bad); }
    .dash-kpi-delta.is-flat { color: var(--dash-ink-3); }

    /* Nilai kosong: tetap satu baris tinggi supaya kartu sejajar. */
    .dash-kpi.is-empty .dash-kpi-value {
        color: var(--dash-ink-3);
        font-weight: 500;
    }

    /* ---------- AKSES HARIAN ---------- */
    .quick-access-card {
        background: var(--dash-surface);
        border: 1px solid var(--dash-line) !important;
        border-radius: var(--dash-radius-sm);
        transition: border-color .16s var(--dash-ease), box-shadow .16s var(--dash-ease);
    }

    .quick-access-card:hover {
        border-color: var(--dash-line-2) !important;
        box-shadow: var(--dash-shadow-hover) !important;
    }

    .quick-access-card .quick-access-icon {
        width: 32px;
        height: 32px;
        flex: 0 0 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        color: var(--dash-ink-2);
        background: var(--dash-surface-2);
        transition: color .16s var(--dash-ease), background-color .16s var(--dash-ease);
    }

    .quick-access-card:hover .quick-access-icon {
        color: var(--dash-navy);
        background: var(--dash-navy-soft);
    }

    .quick-access-card .quick-access-arrow {
        color: var(--dash-ink-3);
        transition: transform .16s var(--dash-ease), color .16s var(--dash-ease);
    }

    .quick-access-card:hover .quick-access-arrow {
        color: var(--dash-ink-2);
        transform: translateX(2px);
    }

    /* ---------- STAT WIDGET (widget lama) ---------- */
    .widget-stat-value {
        font-size: 24px;
        font-weight: 600;
        line-height: 1.15;
        letter-spacing: -.01em;
        font-variant-numeric: tabular-nums;
        color: var(--dash-ink);
    }

    .dash-label {
        font-size: 12px;
        font-weight: 500;
        letter-spacing: 0;
        text-transform: none;
        color: var(--dash-ink-2);
        margin-bottom: 6px;
    }

    .dashboard-role .avatar-title {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        border-radius: var(--dash-radius-sm) !important;
    }

    /* ---------- BADGE ---------- */
    .dashboard-role .badge {
        font-size: 11px;
        font-weight: 500;
        letter-spacing: 0;
        padding: 3px 8px;
        border-radius: 6px;
    }

    /* Tombol netral untuk aksi widget. Warna semantik tetap dipakai hanya
       pada aksi yang benar-benar menyatakan status (setujui/tolak). */
    .dashboard-role .btn[class*="btn-soft-"],
    .dashboard-role .btn.btn-primary-subtle {
        background: var(--dash-surface-2);
        border: 1px solid var(--dash-line);
        color: var(--dash-ink-2);
    }

    .dashboard-role .btn[class*="btn-soft-"]:hover,
    .dashboard-role .btn.btn-primary-subtle:hover {
        background: var(--dash-surface);
        border-color: var(--dash-line-2);
        color: var(--dash-ink);
    }

    /* ---------- GRAFIK ---------- */
    .widget-chart-container {
        min-height: 240px;
    }

    .dashboard-role .apexcharts-legend-text {
        font-size: 12px !important;
        color: var(--dash-ink-2) !important;
    }

    .dashboard-role .apexcharts-xaxis-label,
    .dashboard-role .apexcharts-yaxis-label {
        font-size: 11px !important;
        fill: var(--dash-ink-3) !important;
    }

    .dashboard-role .apexcharts-gridline {
        stroke: var(--dash-line) !important;
    }

    .dashboard-role .apexcharts-tooltip {
        border: 1px solid var(--dash-line-2) !important;
        box-shadow: 0 4px 12px rgba(23, 33, 43, .08) !important;
        font-size: 12px !important;
    }

    /* ---------- TABEL & DAFTAR ---------- */
    .widget-table-scroll {
        max-height: 320px;
        overflow-y: auto;
    }

    .dashboard-role .table {
        --vz-table-cell-padding-y: 10px;
        --vz-table-cell-padding-x: 16px;
        font-size: 13px;
        margin-bottom: 0;
        color: var(--dash-ink);
        border-color: var(--dash-line);
    }

    .dashboard-role .table thead th {
        font-size: 11px;
        font-weight: 500;
        letter-spacing: 0;
        text-transform: none;
        color: var(--dash-ink-3);
        background: transparent;
        border-bottom: 1px solid var(--dash-line);
        white-space: nowrap;
    }

    .dashboard-role .table td,
    .dashboard-role .table th {
        border-color: var(--dash-line);
    }

    .dashboard-role .table tbody tr:hover > * {
        background: var(--dash-surface-2);
    }

    .dashboard-role .table .num {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .dashboard-role .list-group-item {
        padding: 12px 20px;
        font-size: 13px;
        background: transparent;
        border-color: var(--dash-line);
        color: var(--dash-ink);
    }

    /* ---------- KETERANGAN GRAFIK ---------- */
    .dash-chart-caption {
        margin: 2px 0 0;
        font-size: 12px;
        line-height: 1.4;
        color: var(--dash-ink-3);
    }

    .dash-chart-note {
        margin: 8px 0 0;
        font-size: 11px;
        color: var(--dash-ink-3);
    }

    .dash-chart-figure {
        font-size: 22px;
        font-weight: 600;
        line-height: 1.1;
        font-variant-numeric: tabular-nums;
        color: var(--dash-ink);
        white-space: nowrap;
    }

    .dash-chart-unit {
        font-size: 12px;
        font-weight: 500;
        color: var(--dash-ink-3);
    }

    .dash-chart-totals {
        display: flex;
        gap: 14px;
        font-size: 12px;
        color: var(--dash-ink-2);
        white-space: nowrap;
    }

    .dash-chart-totals b {
        font-weight: 600;
        font-variant-numeric: tabular-nums;
        color: var(--dash-ink);
    }

    /* Legenda buatan sendiri: Apex hanya menampilkan nama seri, di sini
       ikut ditampilkan jumlah dan porsinya. */
    .dash-legend {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-top: 12px;
    }

    .dash-legend-row {
        display: grid;
        grid-template-columns: 8px 1fr auto auto;
        align-items: center;
        gap: 8px;
        font-size: 12px;
    }

    .dash-legend-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    .dash-legend-label { color: var(--dash-ink-2); }

    .dash-legend-value {
        color: var(--dash-ink);
        font-weight: 600;
        font-variant-numeric: tabular-nums;
    }

    .dash-legend-share {
        min-width: 44px;
        text-align: right;
        color: var(--dash-ink-3);
        font-variant-numeric: tabular-nums;
    }

    .dash-tt {
        display: flex;
        flex-direction: column;
        gap: 1px;
        padding: 8px 10px;
        font-size: 12px;
        color: var(--dash-ink);
    }

    .dash-tt-title {
        font-weight: 600;
        margin-bottom: 2px;
    }

    .dash-tt-value {
        font-size: 14px;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
    }

    .dash-tt-meta { color: var(--dash-ink-3); }

    /* ---------- KETERISIAN ROMBEL (bar) ---------- */
    .fill-list {
        display: flex;
        flex-direction: column;
    }

    .fill-row {
        display: grid;
        grid-template-columns: 56px 62px 1fr 52px;
        align-items: center;
        gap: 10px;
        padding: 7px 0;
        border-bottom: 1px solid var(--dash-line);
    }

    .fill-row:last-child { border-bottom: 0; }

    .fill-name {
        font-size: 12px;
        font-weight: 600;
        color: var(--dash-ink);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .fill-meta {
        font-size: 11px;
        color: var(--dash-ink-3);
        font-variant-numeric: tabular-nums;
    }

    .fill-track {
        display: block;
        width: 100%;
        height: 6px;
        border-radius: 3px;
        background: var(--dash-line);
        overflow: hidden;
    }

    .fill-bar {
        display: block;
        width: 100%;
        height: 100%;
        border-radius: 3px;
        background: var(--dash-navy-2);
        /* scaleX, bukan width: tidak memicu layout ulang saat berjalan. */
        transform: scaleX(0);
        transform-origin: left center;
        transition: transform .9s var(--dash-ease);
    }

    .fill-bar.is-over { background: var(--dash-bad); }
    .fill-bar.is-low { background: var(--dash-warn); }

    .fill-pct {
        font-size: 12px;
        color: var(--dash-ink-2);
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    /* ---------- QUICK ACTION (widget lama) ---------- */
    .dashboard-role .dash-quick .btn {
        height: 100%;
        border-style: solid;
        border-color: var(--dash-line);
        transition: border-color .16s var(--dash-ease), background-color .16s var(--dash-ease);
    }

    .dashboard-role .dash-quick .btn:hover {
        border-color: var(--dash-line-2);
        background: var(--dash-surface-2);
    }

    /* ---------- EMPTY STATE ---------- */
    .dash-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        gap: 6px;
        padding: 28px 16px;
        color: var(--dash-ink-3);
    }

    .dash-empty i {
        font-size: 20px;
        opacity: .5;
        line-height: 1;
    }

    .dash-empty p {
        margin: 0;
        font-size: 13px;
        font-weight: 400;
        color: var(--dash-ink-2);
    }

    .dash-empty small {
        font-size: 12px;
        color: var(--dash-ink-3);
        opacity: 1;
    }

    /* ---------- TINDAK LANJUT ---------- */
    .dash-action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 500;
        padding: 7px 12px;
        border-radius: 8px;
        border: 1px solid var(--dash-line-2);
        color: var(--dash-ink-2);
        background: transparent;
        text-decoration: none;
        transition: background-color .16s var(--dash-ease), color .16s var(--dash-ease), border-color .16s var(--dash-ease);
    }

    .dash-action:hover {
        background: var(--dash-surface-2);
        border-color: var(--dash-ink-3);
        color: var(--dash-ink);
    }

    .dash-action.is-primary {
        background: var(--dash-navy);
        border-color: var(--dash-navy);
        color: #fff;
    }

    .dash-action.is-primary:hover {
        background: var(--dash-navy-2);
        border-color: var(--dash-navy-2);
        color: #fff;
    }

    /* ---------- TEMUAN ---------- */
    .finding-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 12px 0;
        border-bottom: 1px solid var(--dash-line);
    }

    .finding-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .finding-main {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        min-width: 0;
    }

    /* Penanda tingkat: garis tipis, bukan blok ikon berwarna. */
    .finding-flag {
        width: 2px;
        align-self: stretch;
        border-radius: 2px;
        flex: 0 0 2px;
        background: var(--dash-line-2);
    }

    .finding-flag.is-warning { background: var(--dash-warn); }
    .finding-flag.is-danger { background: var(--dash-bad); }
    .finding-flag.is-info { background: var(--dash-navy-2); }

    .finding-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--dash-ink);
        text-decoration: none;
    }

    a.finding-title:hover {
        color: var(--dash-navy-2);
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    .finding-desc {
        font-size: 12px;
        color: var(--dash-ink-2);
        margin-top: 2px;
    }

    .finding-count {
        font-size: 13px;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
        color: var(--dash-ink-2);
        white-space: nowrap;
    }

    /* ---------- KALDIK ---------- */
    .kaldik-row {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid var(--dash-line);
    }

    .kaldik-row:last-child {
        border-bottom: 0 !important;
        padding-bottom: 0;
    }

    .kaldik-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        margin-top: 7px;
        flex-shrink: 0;
        box-shadow: none;
    }

    .kaldik-name {
        font-size: 13px;
        font-weight: 600;
        color: var(--dash-ink);
    }

    .kaldik-meta {
        font-size: 12px;
        color: var(--dash-ink-3);
        margin-top: 2px;
        font-variant-numeric: tabular-nums;
    }

    /* ---------- JADWAL SHALAT (garis waktu, bukan kotak) ---------- */
    .prayer-track {
        display: flex;
        align-items: stretch;
        border: 1px solid var(--dash-line);
        border-radius: var(--dash-radius-sm);
        overflow: hidden;
    }

    .prayer-item {
        position: relative;
        flex: 1 1 0;
        padding: 12px 6px 10px;
        text-align: center;
        background: transparent;
        border: 0;
        border-right: 1px solid var(--dash-line);
        border-radius: 0;
        transition: background-color .16s var(--dash-ease);
    }

    .prayer-item:last-child {
        border-right: 0;
    }

    .prayer-item:hover {
        transform: none;
        box-shadow: none;
        background: var(--dash-surface-2);
    }

    .prayer-label {
        font-size: 11px;
        font-weight: 500;
        letter-spacing: 0;
        text-transform: none;
        color: var(--dash-ink-3);
        margin-bottom: 4px;
    }

    .prayer-time {
        font-size: 17px;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
        color: var(--dash-ink);
    }

    .prayer-item.is-past {
        opacity: .55;
    }

    .prayer-item.is-next {
        background: var(--dash-navy-soft);
        border-color: var(--dash-line);
        animation: none;
    }

    .prayer-item.is-next .prayer-label {
        color: var(--dash-navy-2);
    }

    .prayer-item.is-next::before {
        content: '';
        position: absolute;
        inset: 0 0 auto 0;
        height: 2px;
        background: var(--dash-gold);
    }

    /* ---------- ANIMASI ---------- */
    @keyframes dashRise {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: none; }
    }

    /* Satu animasi masuk untuk seluruh halaman, dijalankan JS dengan jeda
       bertingkat. Tanpa JS tetap terlihat (opacity tidak pernah 0). */
    .dash-reveal {
        animation: dashRise .42s var(--dash-ease) both;
    }

    @media (prefers-reduced-motion: reduce) {
        .dash-reveal,
        .dashboard-role .card,
        .quick-access-card,
        .quick-access-card .quick-access-icon,
        .quick-access-card .quick-access-arrow,
        .prayer-item {
            animation: none !important;
            transition: none !important;
        }
    }

    /* ---------- RESPONSIVE ---------- */
    @media (max-width: 991.98px) {
        .dash-section-count { display: none; }
    }
</style>
<style>
    /* ---------- HEADER & FILTER DASHBOARD ---------- */
    .dash-header .card-body { padding: 20px; }

    .dash-header h4 {
        font-size: 20px;
        font-weight: 600;
        letter-spacing: -.01em;
        color: var(--dash-ink);
    }

    /* Tombol ekspor: netral, bukan hijau/merah dekoratif. */
    .dash-header .btn-export {
        background: transparent;
        border: 1px solid var(--dash-line-2);
        color: var(--dash-ink-2);
        font-size: 12px;
        font-weight: 500;
        padding: 5px 10px;
        border-radius: 8px;
        transition: background-color .16s var(--dash-ease), color .16s var(--dash-ease), border-color .16s var(--dash-ease);
    }

    .dash-header .btn-export:hover {
        background: var(--dash-surface-2);
        border-color: var(--dash-ink-3);
        color: var(--dash-ink);
    }

    .dash-header .form-label {
        font-size: 12px;
        font-weight: 500;
        color: var(--dash-ink-2);
        margin-bottom: 4px;
    }

    .dash-header .form-select-sm {
        font-size: 13px;
        border-color: var(--dash-line-2);
        border-radius: 8px;
        color: var(--dash-ink);
        background-color: var(--dash-surface);
    }

    .dash-header .btn-primary {
        background: var(--dash-navy);
        border-color: var(--dash-navy);
        font-size: 13px;
        font-weight: 500;
        border-radius: 8px;
        padding: 5px 14px;
    }

    .dash-header .btn-primary:hover {
        background: var(--dash-navy-2);
        border-color: var(--dash-navy-2);
    }

    .dash-header .btn-light {
        background: transparent;
        border-color: var(--dash-line-2) !important;
        color: var(--dash-ink-2);
        border-radius: 8px;
    }

    /* ---------- CAROUSEL INFO ---------- */
    .info-carousel-card .carousel-inner { border-radius: 0 0 14px 14px; overflow: hidden; }
    .info-slide { position: relative; display: block; color: #fff; }
    .info-slide-image { height: 260px; object-fit: cover; filter: brightness(.78); }
    .info-slide-caption {
        position: absolute; inset: auto 0 0 0;
        padding: 20px 24px;
        background: linear-gradient(180deg, rgba(15, 23, 42, 0) 0%, rgba(15, 23, 42, .86) 70%);
    }
    .info-slide-caption h4 { font-size: 18px; font-weight: 600; text-shadow: none; }
    @media (max-width: 575.98px) {
        .info-slide-image { height: 200px; }
        .info-slide-caption { padding: 14px 16px; }
        .info-slide-caption h4 { font-size: 15px; }
    }

    /* ---------- MODAL BROADCAST ---------- */
    [data-alim-broadcast-modal] .modal-content { border-radius: 14px; }
    [data-alim-broadcast-modal] .modal-body p { line-height: 1.7; }
</style>
