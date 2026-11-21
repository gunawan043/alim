{{--
    LAPORAN — SISTEM VISUAL BERSAMA
    ===============================
    Melanjutkan bahasa visual dashboard: satu ramp netral, satu aksen navy
    institusi, emas hanya untuk penanda. Halaman laporan dibaca seperti kop
    surat: judul, periode, lalu tabel dan satu grafik utama.

    Semua aturan di bawah di-scope ke .laporan-page supaya tidak menabrak
    halaman lain yang masih memakai kartu Bootstrap biasa.
--}}
<style>
    /* ---------- TOKEN ---------- */
    .laporan-page {
        --lap-ink: #17212b;
        --lap-ink-2: #5a6472;
        --lap-ink-3: #8b949f;
        --lap-surface: #ffffff;
        --lap-surface-2: #f6f7f8;
        --lap-surface-3: #eef0f2;
        --lap-line: rgba(23, 33, 43, .08);
        --lap-line-2: rgba(23, 33, 43, .16);
        --lap-navy: #00416c;
        --lap-navy-2: #005981;
        --lap-navy-soft: rgba(0, 65, 108, .06);
        --lap-gold: #ffae01;
        --lap-gold-ink: #8a5a00;
        --lap-ok: #2f7d4f;
        --lap-warn: #9a6a00;
        --lap-bad: #a4462f;
        --lap-radius: 14px;
        --lap-radius-sm: 10px;
        --lap-ease: cubic-bezier(.22, .61, .36, 1);
        color: var(--lap-ink);
        font-variant-numeric: tabular-nums;
    }

    [data-bs-theme="dark"] .laporan-page {
        --lap-ink: #e9edf2;
        --lap-ink-2: #a6afba;
        --lap-ink-3: #7b848f;
        --lap-surface: #1b222b;
        --lap-surface-2: #222a34;
        --lap-surface-3: #2a323d;
        --lap-line: rgba(255, 255, 255, .09);
        --lap-line-2: rgba(255, 255, 255, .18);
        --lap-navy: #0a4a75;
        --lap-navy-2: #0a5b8c;
        --lap-navy-soft: rgba(90, 160, 210, .12);
        --lap-gold-ink: #ffc64d;
        --lap-ok: #6bbf8b;
        --lap-warn: #e0b04a;
        --lap-bad: #e0846c;
    }

    /* ---------- KEPALA HALAMAN ---------- */
    .laporan-page .lap-head {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        justify-content: space-between;
        gap: 12px 20px;
        margin-bottom: 16px;
    }

    .laporan-page .lap-head-title {
        font-size: 17px;
        font-weight: 600;
        line-height: 1.3;
        margin: 0 0 4px;
        color: var(--lap-ink);
    }

    .laporan-page .lap-head-desc {
        font-size: 13px;
        line-height: 1.55;
        color: var(--lap-ink-2);
        margin: 0;
        max-width: 70ch;
    }

    .laporan-page .lap-head-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
    }

    /* ---------- TOMBOL ---------- */
    .laporan-page .lap-btn,
    .laporan-page .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        height: 36px;
        padding: 0 14px;
        font-size: 13px;
        font-weight: 500;
        line-height: 1;
        border-radius: var(--lap-radius-sm);
        border: 1px solid var(--lap-line-2);
        background: var(--lap-surface);
        color: var(--lap-ink);
        box-shadow: none;
        transition: background-color .16s var(--lap-ease), border-color .16s var(--lap-ease), color .16s var(--lap-ease);
        white-space: nowrap;
    }

    .laporan-page .lap-btn:hover,
    .laporan-page .btn:hover {
        background: var(--lap-surface-2);
        border-color: var(--lap-line-2);
        color: var(--lap-ink);
    }

    .laporan-page .lap-btn-primary,
    .laporan-page .btn-primary {
        background: var(--lap-navy);
        border-color: var(--lap-navy);
        color: #fff;
    }

    .laporan-page .lap-btn-primary:hover,
    .laporan-page .btn-primary:hover {
        background: var(--lap-navy-2);
        border-color: var(--lap-navy-2);
        color: #fff;
    }

    .laporan-page .lap-btn-sm,
    .laporan-page .btn-sm {
        height: 30px;
        padding: 0 10px;
        font-size: 12px;
    }

    .laporan-page .lap-btn-icon {
        width: 36px;
        padding: 0;
    }

    .laporan-page .btn i,
    .laporan-page .lap-btn i {
        font-size: 14px;
        opacity: .75;
    }

    /* Tombol bawaan template yang masih tersisa di halaman laporan */
    .laporan-page .btn-light,
    .laporan-page .btn-success,
    .laporan-page .btn-danger,
    .laporan-page .btn-outline-secondary {
        background: var(--lap-surface);
        border-color: var(--lap-line-2);
        color: var(--lap-ink);
    }

    .laporan-page .btn-light:hover,
    .laporan-page .btn-success:hover,
    .laporan-page .btn-danger:hover {
        background: var(--lap-surface-2);
        color: var(--lap-ink);
    }

    /* ---------- TAB ---------- */
    .laporan-page .lap-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0;
        border-bottom: 1px solid var(--lap-line);
        margin-bottom: 16px;
    }

    .laporan-page .lap-tab {
        position: relative;
        display: inline-flex;
        align-items: center;
        height: 40px;
        padding: 0 14px;
        font-size: 13px;
        font-weight: 500;
        color: var(--lap-ink-2);
        text-decoration: none;
        white-space: nowrap;
        transition: color .16s var(--lap-ease);
    }

    .laporan-page .lap-tab:hover {
        color: var(--lap-ink);
    }

    .laporan-page .lap-tab.is-active {
        color: var(--lap-ink);
        font-weight: 600;
    }

    .laporan-page .lap-tab.is-active::after {
        content: "";
        position: absolute;
        left: 14px;
        right: 14px;
        bottom: -1px;
        height: 2px;
        background: var(--lap-gold);
    }

    /* ---------- KOP PERIODE ---------- */
    .laporan-page .lap-period {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        justify-content: space-between;
        gap: 6px 24px;
        padding: 12px 16px 14px;
        margin-bottom: 16px;
        background: var(--lap-surface);
        border: 1px solid var(--lap-line);
        border-radius: var(--lap-radius) var(--lap-radius) 0 0;
        position: relative;
    }

    .laporan-page .lap-period::after {
        content: "";
        position: absolute;
        left: 0;
        right: 0;
        bottom: -1px;
        height: 2px;
        background: var(--lap-gold);
    }

    .laporan-page .lap-period-main {
        font-size: 13px;
        font-weight: 500;
        color: var(--lap-ink);
    }

    .laporan-page .lap-period-main span {
        color: var(--lap-ink-3);
        font-weight: 400;
        margin-right: 6px;
    }

    .laporan-page .lap-period-note {
        font-size: 12px;
        line-height: 1.5;
        color: var(--lap-ink-2);
        max-width: 72ch;
    }

    .laporan-page .lap-period + .lap-kpi,
    .laporan-page .lap-period + .row,
    .laporan-page .lap-period + .lap-grid {
        margin-top: 0;
    }

    /* ---------- KPI ---------- */
    .laporan-page .lap-kpi {
        display: flex;
        flex-wrap: wrap;
        gap: 1px;
        background: var(--lap-line);
        border: 1px solid var(--lap-line);
        border-radius: var(--lap-radius);
        overflow: hidden;
        margin-bottom: 16px;
    }

    .laporan-page .lap-kpi-cell {
        flex: 1 1 0;
        min-width: 168px;
        background: var(--lap-surface);
        padding: 16px 18px 18px;
    }

    .laporan-page .lap-kpi-label {
        font-size: 12px;
        font-weight: 500;
        color: var(--lap-ink-2);
        margin: 0 0 8px;
        line-height: 1.35;
    }

    .laporan-page .lap-kpi-value {
        display: flex;
        align-items: baseline;
        gap: 4px;
        font-size: 26px;
        font-weight: 500;
        line-height: 1.1;
        letter-spacing: -.01em;
        color: var(--lap-ink);
        font-variant-numeric: tabular-nums;
    }

    .laporan-page .lap-kpi-unit {
        font-size: 14px;
        font-weight: 400;
        color: var(--lap-ink-3);
    }

    .laporan-page .lap-kpi-note {
        font-size: 12px;
        line-height: 1.45;
        color: var(--lap-ink-3);
        margin: 6px 0 0;
    }

    .laporan-page .lap-kpi-note.is-ok { color: var(--lap-ok); }
    .laporan-page .lap-kpi-note.is-warn { color: var(--lap-warn); }
    .laporan-page .lap-kpi-note.is-bad { color: var(--lap-bad); }

    /* ---------- KARTU ---------- */
    .laporan-page .card {
        background: var(--lap-surface);
        border: 1px solid var(--lap-line);
        border-radius: var(--lap-radius);
        box-shadow: none;
        margin-bottom: 16px;
    }

    .laporan-page .card-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 8px 16px;
        padding: 14px 18px;
        background: transparent;
        border-bottom: 1px solid var(--lap-line);
        min-height: 0;
    }

    .laporan-page .card-body {
        padding: 18px;
    }

    .laporan-page .card-body.p-0 {
        padding: 0;
    }

    .laporan-page .card-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--lap-ink);
        margin: 0;
        line-height: 1.35;
    }

    /* Ikon dekoratif di judul kartu tidak dipakai lagi */
    .laporan-page .card-title > i,
    .laporan-page .card-header > i {
        display: none;
    }

    .laporan-page .card-title small,
    .laporan-page .card-subtitle {
        display: block;
        font-size: 12px;
        font-weight: 400;
        color: var(--lap-ink-3);
        margin-top: 2px;
    }

    .laporan-page .lap-meta {
        font-size: 12px;
        color: var(--lap-ink-3);
        font-variant-numeric: tabular-nums;
    }

    /* ---------- FILTER ---------- */
    .laporan-page .lap-filters {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 10px 12px;
    }

    .laporan-page .lap-field {
        flex: 1 1 145px;
        min-width: 140px;
        max-width: 260px;
    }

    .laporan-page .lap-actions {
        display: flex;
        gap: 8px;
        margin-left: auto;
        padding-bottom: 1px;
    }

    .laporan-page .form-label {
        font-size: 12px;
        font-weight: 500;
        color: var(--lap-ink-2);
        margin-bottom: 6px;
    }

    .laporan-page .form-control,
    .laporan-page .form-select {
        height: 36px;
        padding: 0 12px;
        font-size: 13px;
        color: var(--lap-ink);
        background-color: var(--lap-surface);
        border: 1px solid var(--lap-line-2);
        border-radius: var(--lap-radius-sm);
        box-shadow: none;
    }

    .laporan-page .form-select {
        padding-right: 32px;
    }

    .laporan-page .form-control::placeholder {
        color: var(--lap-ink-3);
    }

    .laporan-page .form-control:focus,
    .laporan-page .form-select:focus {
        border-color: var(--lap-navy-2);
        box-shadow: 0 0 0 3px var(--lap-navy-soft);
        background-color: var(--lap-surface);
        color: var(--lap-ink);
    }

    .laporan-page .form-control-sm,
    .laporan-page .form-select-sm {
        height: 30px;
        font-size: 12px;
    }

    /* ---------- TABEL ---------- */
    .laporan-page .table {
        margin: 0;
        color: var(--lap-ink);
        font-size: 13px;
        border-color: var(--lap-line);
        --vz-table-bg: transparent;
        --vz-table-color: var(--lap-ink);
        --vz-table-border-color: var(--lap-line);
    }

    .laporan-page .table > thead th,
    .laporan-page .table > thead td {
        padding: 10px 12px;
        font-size: 12px;
        font-weight: 500;
        color: var(--lap-ink-2);
        background: var(--lap-surface-2);
        border-bottom: 1px solid var(--lap-line);
        white-space: nowrap;
    }

    .laporan-page .table > tbody td,
    .laporan-page .table > tbody th {
        padding: 10px 12px;
        border-bottom: 1px solid var(--lap-line);
        vertical-align: middle;
    }

    .laporan-page .table > tbody tr:last-child td {
        border-bottom: 0;
    }

    .laporan-page .table-hover > tbody > tr:hover > * {
        background: var(--lap-surface-2);
        --vz-table-accent-bg: var(--lap-surface-2);
    }

    .laporan-page .table .num,
    .laporan-page .table td.text-end {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .laporan-page .table .muted {
        color: var(--lap-ink-3);
    }

    .laporan-page .lap-table-name {
        font-weight: 500;
        color: var(--lap-ink);
        line-height: 1.35;
    }

    .laporan-page .lap-table-sub {
        font-size: 11px;
        color: var(--lap-ink-3);
        font-variant-numeric: tabular-nums;
    }

    .laporan-page .lap-flag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: var(--lap-ink-2);
        line-height: 1.4;
    }

    .laporan-page .lap-flag + .lap-table-sub {
        padding-left: 12px;
        margin-top: 1px;
    }

    .laporan-page .lap-flag::before {
        content: '';
        flex: none;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: transparent;
        box-shadow: inset 0 0 0 1.5px var(--lap-ink-3);
    }

    .laporan-page .lap-flag.is-done {
        color: var(--lap-ink);
    }

    .laporan-page .lap-flag.is-done::before {
        background: var(--lap-navy);
        box-shadow: none;
    }

    /* ---------- BADGE / STATUS ---------- */
    .laporan-page .badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 2px 8px;
        font-size: 11px;
        font-weight: 500;
        line-height: 1.6;
        border-radius: 6px;
        border: 1px solid var(--lap-line-2);
        background: var(--lap-surface-2);
        color: var(--lap-ink-2);
    }

    .laporan-page .badge::before {
        content: "";
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: currentColor;
        opacity: .7;
    }

    .laporan-page .badge:empty::before {
        display: none;
    }

    .laporan-page .badge.bg-success,
    .laporan-page .badge.bg-success-subtle {
        background: var(--lap-surface-2);
        border-color: var(--lap-line-2);
        color: var(--lap-ok);
    }

    .laporan-page .badge.bg-warning,
    .laporan-page .badge.bg-warning-subtle {
        background: var(--lap-surface-2);
        border-color: var(--lap-line-2);
        color: var(--lap-warn);
    }

    .laporan-page .badge.bg-danger,
    .laporan-page .badge.bg-danger-subtle {
        background: var(--lap-surface-2);
        border-color: var(--lap-line-2);
        color: var(--lap-bad);
    }

    .laporan-page .badge.bg-secondary,
    .laporan-page .badge.bg-secondary-subtle,
    .laporan-page .badge.bg-info,
    .laporan-page .badge.bg-info-subtle,
    .laporan-page .badge.bg-primary,
    .laporan-page .badge.bg-primary-subtle {
        background: var(--lap-surface-2);
        border-color: var(--lap-line-2);
        color: var(--lap-ink-2);
    }

    .laporan-page .text-success { color: var(--lap-ok) !important; }
    .laporan-page .text-danger { color: var(--lap-bad) !important; }
    .laporan-page .text-warning { color: var(--lap-warn) !important; }
    .laporan-page .text-muted,
    .laporan-page .text-secondary { color: var(--lap-ink-3) !important; }

    /* ---------- GRAFIK ---------- */
    .laporan-page .lap-chart {
        position: relative;
        width: 100%;
    }

    .laporan-page .lap-chart-legend {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 4px 16px;
        padding: 12px 18px 0;
    }

    .laporan-page .lap-legend-item {
        display: inline-flex;
        align-items: baseline;
        gap: 6px;
        font-size: 12px;
        color: var(--lap-ink-2);
    }

    .laporan-page .lap-legend-swatch {
        width: 8px;
        height: 8px;
        border-radius: 2px;
        flex: none;
        transform: translateY(-1px);
    }

    .laporan-page .lap-legend-value {
        font-size: 12px;
        font-weight: 600;
        color: var(--lap-ink);
        font-variant-numeric: tabular-nums;
    }

    .laporan-page .lap-tooltip {
        padding: 8px 10px;
        font-size: 12px;
        line-height: 1.5;
        color: var(--lap-ink);
        background: var(--lap-surface);
        border: 1px solid var(--lap-line-2);
        border-radius: var(--lap-radius-sm);
        box-shadow: 0 4px 14px rgba(23, 33, 43, .10);
        min-width: 132px;
    }

    .laporan-page .lap-tooltip-title {
        font-weight: 600;
        margin-bottom: 4px;
    }

    .laporan-page .lap-tooltip-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        color: var(--lap-ink-2);
    }

    .laporan-page .lap-tooltip-row b {
        font-weight: 600;
        color: var(--lap-ink);
        font-variant-numeric: tabular-nums;
    }

    /* ---------- DAFTAR NILAI (label kiri, angka kanan) ---------- */
    .laporan-page .lap-list {
        margin: 0;
    }

    .laporan-page .lap-list-row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 16px;
        padding: 9px 0;
        border-bottom: 1px solid var(--lap-line);
        font-size: 13px;
    }

    .laporan-page .lap-list-row:last-child {
        border-bottom: 0;
    }

    .laporan-page .lap-list-label {
        color: var(--lap-ink-2);
        line-height: 1.45;
    }

    .laporan-page .lap-list-value {
        font-weight: 500;
        color: var(--lap-ink);
        text-align: right;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }

    .laporan-page .lap-list-value.is-ok { color: var(--lap-ok); }
    .laporan-page .lap-list-value.is-warn { color: var(--lap-warn); }
    .laporan-page .lap-list-value.is-bad { color: var(--lap-bad); }

    /* ---------- BAR SEBARAN ---------- */
    .laporan-page .lap-bars {
        display: grid;
        gap: 2px;
    }

    /* Bar tipis di dalam sel tabel (keterisian rombel) */
    .laporan-page .lap-mini {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-width: 116px;
        justify-content: flex-end;
    }

    .laporan-page .lap-mini-track {
        flex: 1 1 auto;
        height: 5px;
        min-width: 48px;
        border-radius: 3px;
        background: var(--lap-surface-3);
        overflow: hidden;
    }

    .laporan-page .lap-mini-fill {
        display: block;
        height: 100%;
        border-radius: 3px;
        background: var(--lap-navy-2);
        transform: scaleX(0);
        transform-origin: left center;
        transition: transform .8s var(--lap-ease);
    }

    .laporan-page .lap-mini-fill.is-full { background: var(--lap-bad); }
    .laporan-page .lap-mini-fill.is-low { background: var(--lap-warn); }

    /* Bar komposisi 100% (satu batang bertumpuk) */
    .laporan-page .lap-stack {
        display: flex;
        height: 10px;
        border-radius: 5px;
        overflow: hidden;
        background: var(--lap-surface-3);
    }

    .laporan-page .lap-stack span {
        display: block;
        height: 100%;
    }

    .laporan-page .lap-bar-row {
        display: grid;
        grid-template-columns: minmax(96px, 1.1fr) minmax(0, 2fr) 56px;
        align-items: center;
        gap: 12px;
        padding: 7px 0;
        font-size: 13px;
    }

    .laporan-page .lap-bar-name {
        color: var(--lap-ink-2);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .laporan-page .lap-bar-track {
        height: 6px;
        border-radius: 3px;
        background: var(--lap-surface-3);
        overflow: hidden;
    }

    .laporan-page .lap-bar-fill {
        height: 100%;
        width: 100%;
        border-radius: 3px;
        background: var(--lap-navy-2);
        transform: scaleX(0);
        transform-origin: left center;
        transition: transform .8s var(--lap-ease);
    }

    .laporan-page .lap-bar-fill.is-accent { background: var(--lap-gold); }
    .laporan-page .lap-bar-fill.is-bad { background: var(--lap-bad); }

    .laporan-page .lap-bar-value {
        text-align: right;
        font-variant-numeric: tabular-nums;
        color: var(--lap-ink);
        font-weight: 500;
    }

    /* ---------- KOSONG ---------- */
    .laporan-page .lap-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 40px 24px;
        text-align: center;
        color: var(--lap-ink-3);
    }

    .laporan-page .lap-empty-title {
        font-size: 13px;
        font-weight: 500;
        color: var(--lap-ink-2);
    }

    .laporan-page .lap-empty-desc {
        font-size: 12px;
        line-height: 1.5;
        max-width: 44ch;
    }

    .laporan-page .lap-empty i {
        display: none;
    }

    /* ---------- CATATAN ---------- */
    .laporan-page .lap-note {
        font-size: 12px;
        line-height: 1.55;
        color: var(--lap-ink-2);
        margin: 0;
    }

    .laporan-page .lap-note strong {
        font-weight: 600;
        color: var(--lap-ink);
    }

    /* Peringatan yang isinya penting: tetap tenang, tetapi terbaca */
    .laporan-page .lap-callout {
        padding: 12px 16px;
        margin-bottom: 16px;
        font-size: 12px;
        line-height: 1.6;
        color: var(--lap-ink-2);
        background: var(--lap-surface-2);
        border: 1px solid var(--lap-line);
        border-left: 2px solid var(--lap-gold);
        border-radius: var(--lap-radius-sm);
    }

    .laporan-page .lap-callout strong {
        font-weight: 600;
        color: var(--lap-ink);
    }

    /* Alert bawaan template diganti catatan netral */
    .laporan-page .alert {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        justify-content: space-between;
        gap: 6px 20px;
        padding: 10px 14px;
        margin-bottom: 16px;
        font-size: 12px;
        color: var(--lap-ink-2);
        background: var(--lap-surface-2);
        border: 1px solid var(--lap-line);
        border-radius: var(--lap-radius-sm);
    }

    .laporan-page .alert strong {
        font-weight: 600;
        color: var(--lap-ink);
    }

    /* ---------- DAFTAR TAUTAN LAPORAN ---------- */
    .laporan-page .lap-links {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        background: var(--lap-surface);
        /* Baris terakhir menimpa garis bawah kartu supaya tidak jadi dua garis. */
        margin-bottom: -1px;
    }

    .laporan-page .lap-link {
        display: block;
        padding: 16px 18px;
        background: var(--lap-surface);
        text-decoration: none;
        border-right: 1px solid var(--lap-line);
        border-bottom: 1px solid var(--lap-line);
        transition: background-color .16s var(--lap-ease);
    }

    .laporan-page .lap-link:nth-child(3n) {
        border-right: 0;
    }

    .laporan-page .lap-link:hover {
        background: var(--lap-surface-2);
    }

    .laporan-page .lap-link-label {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        font-size: 13px;
        font-weight: 600;
        color: var(--lap-ink);
        margin-bottom: 4px;
    }

    .laporan-page .lap-link-label i {
        font-size: 14px;
        color: var(--lap-ink-3);
        transition: transform .16s var(--lap-ease), color .16s var(--lap-ease);
    }

    .laporan-page .lap-link:hover .lap-link-label i {
        color: var(--lap-navy-2);
        transform: translateX(2px);
    }

    .laporan-page .lap-link-desc {
        font-size: 12px;
        line-height: 1.5;
        color: var(--lap-ink-2);
    }

    @media (max-width: 1199.98px) {
        .laporan-page .lap-links {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .laporan-page .lap-link:nth-child(3n) {
            border-right: 1px solid var(--lap-line);
        }

        .laporan-page .lap-link:nth-child(2n) {
            border-right: 0;
        }
    }

    @media (max-width: 575.98px) {
        .laporan-page .lap-links {
            grid-template-columns: minmax(0, 1fr);
        }

        .laporan-page .lap-link,
        .laporan-page .lap-link:nth-child(3n) {
            border-right: 0;
        }
    }

    /* ---------- GRID BANTU ---------- */
    .laporan-page .lap-grid {
        display: grid;
        gap: 16px;
        margin-bottom: 16px;
    }

    .laporan-page .lap-grid-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .laporan-page .lap-grid-main {
        grid-template-columns: minmax(0, 1.9fr) minmax(0, 1fr);
    }

    .laporan-page .lap-grid-3 {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .laporan-page .lap-grid > .card {
        margin-bottom: 0;
    }

    /* Kartu mengikuti tinggi isinya, tidak dipaksa setinggi tetangganya */
    .laporan-page .lap-grid-top {
        align-items: start;
    }

    @media (max-width: 1199.98px) {
        .laporan-page .lap-grid-2,
        .laporan-page .lap-grid-main,
        .laporan-page .lap-grid-3 {
            grid-template-columns: minmax(0, 1fr);
        }
    }

    /* ---------- GERAK ---------- */
    .laporan-page .lap-reveal {
        opacity: 0;
        transform: translateY(6px);
        animation: lapRise .42s var(--lap-ease) forwards;
    }

    @keyframes lapRise {
        to {
            opacity: 1;
            transform: none;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .laporan-page .lap-reveal,
        .laporan-page .lap-bar-fill {
            animation: none !important;
            transition: none !important;
            opacity: 1;
            transform: none;
        }
    }
</style>
