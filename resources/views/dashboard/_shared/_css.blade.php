<style>
    /* =====================================================
       DASHBOARD — SATUAN PENDIDIKAN
       Design language: Skote/Velzon. Clean & consistent.
       Semua style terpusat di sini (bukan per widget).
       ===================================================== */

    .dashboard-role {
        --dash-radius: 0.75rem;
        --dash-border: rgba(56, 65, 74, 0.10);
        --dash-muted: var(--vz-secondary-color);
    }

    /* ---------- GRID AUTO-FILL (flex) ----------
       Setiap baris selalu terisi penuh: widget melebar otomatis
       (flex-grow proporsional) mengisi sisa ruang di barisnya. */
    .dash-grid {
        display: flex;
        flex-wrap: wrap;
        align-items: stretch;
        gap: 1rem;
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

    /* ---------- CARD (dasar, berlaku semua widget) ---------- */
    .dashboard-role .card {
        border: 1px solid var(--dash-border);
        border-radius: var(--dash-radius);
        box-shadow: 0 1px 2px rgba(56, 65, 74, 0.05);
        height: 100%;
        transition: box-shadow .18s ease, transform .18s ease;
    }

    .dashboard-role .card-body {
        padding: 1.1rem 1.15rem;
    }

    /* Header seragam untuk semua card (chart, tabel, list, quick action) */
    .dashboard-role .card-header {
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .85rem 1.15rem;
        background: transparent;
        border-bottom: 1px solid var(--dash-border);
        min-height: 52px;
    }

    .dashboard-role .card-header .card-title {
        flex: 1 1 auto;
        margin: 0;
        font-size: .9rem;
        font-weight: 600;
        line-height: 1.35;
    }

    /* Badge seragam */
    .dashboard-role .badge {
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .01em;
        padding: .32em .6em;
    }

    /* Stat card boleh hover; tabel/list tidak (menghindari distraksi) */
    .dashboard-role .card-animate:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 22px rgba(56, 65, 74, 0.10);
    }

    /* ---------- SECTION ---------- */
    .dash-section {
        margin-bottom: 1.5rem;
    }

    .dash-section-header {
        display: flex;
        align-items: center;
        gap: .6rem;
        margin-bottom: .85rem;
    }

    .dash-section-icon {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(var(--vz-primary-rgb), 0.10);
        color: var(--vz-primary);
        font-size: .95rem;
        flex-shrink: 0;
    }

    .dash-section-title {
        margin: 0;
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--dash-muted);
        white-space: nowrap;
    }

    .dash-section-line {
        flex: 1;
        height: 1px;
        background: linear-gradient(90deg, var(--dash-border), transparent);
    }

    .dash-section-count {
        font-size: .7rem;
        font-weight: 600;
        color: var(--dash-muted);
        background: rgba(56, 65, 74, 0.06);
        border-radius: 999px;
        padding: .18rem .6rem;
        white-space: nowrap;
    }

    /* ---------- AKSES HARIAN (shortcut GTK Satuan Pendidikan) ---------- */
    .quick-access-card {
        background: var(--vz-card-bg, #fff);
        border: 1px solid var(--dash-border) !important;
        border-radius: .65rem;
        transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
    }
    .quick-access-card:hover {
        transform: translateY(-2px);
        border-color: rgba(var(--vz-primary-rgb), .25) !important;
        box-shadow: 0 8px 18px rgba(56, 65, 74, .10) !important;
    }
    .quick-access-icon {
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    /* ---------- HERO (informasi utama, emphasis tertinggi) ---------- */
    .welcome-banner {
        position: relative;
        overflow: hidden;
        border-radius: var(--dash-radius);
        padding: 1.5rem 1.6rem;
        color: #fff;
        background: linear-gradient(132deg, #35446f 0%, var(--vz-primary) 100%);
        box-shadow: 0 8px 20px rgba(53, 68, 111, 0.18);
    }

    .welcome-banner .banner-eyebrow {
        display: inline-flex;
        align-items: center;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .07em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.9);
        background: rgba(255, 255, 255, 0.12);
        border-radius: 999px;
        padding: .25rem .7rem;
        margin-bottom: .6rem;
    }

    .welcome-banner .banner-title {
        color: #fff;
        font-weight: 700;
        font-size: 1.35rem;
        letter-spacing: -.01em;
        margin: 0 0 .45rem;
    }

    .welcome-banner .banner-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .35rem;
        color: rgba(255, 255, 255, 0.85);
        font-size: .82rem;
    }

    .welcome-banner .banner-dot {
        opacity: .55;
        margin: 0 .3rem;
    }

    .welcome-banner .banner-chip {
        display: inline-flex;
        align-items: center;
        font-size: .7rem;
        font-weight: 600;
        color: #fff;
        background: rgba(255, 255, 255, 0.14);
        border-radius: 999px;
        padding: .22rem .6rem;
    }

    .welcome-banner .banner-icon-wrap {
        width: 62px;
        height: 62px;
        flex-shrink: 0;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: rgba(255, 255, 255, 0.92);
        background: rgba(255, 255, 255, 0.12);
    }

    /* ---------- STAT WIDGET (KPI) ---------- */
    .widget-stat-value {
        font-size: 1.6rem;
        font-weight: 700;
        line-height: 1.15;
        letter-spacing: -.01em;
        color: var(--vz-heading-color);
    }

    .dash-label {
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: var(--dash-muted);
        margin-bottom: .45rem;
    }

    .dashboard-role .avatar-title {
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        border-radius: 12px !important;
    }

    /* ---------- CHART ---------- */
    .widget-chart-container {
        min-height: 250px;
    }

    /* ---------- TABLE & LIST ---------- */
    .widget-table-scroll {
        max-height: 330px;
        overflow-y: auto;
    }

    .dashboard-role .table {
        --vz-table-cell-padding-y: .6rem;
        --vz-table-cell-padding-x: .9rem;
        font-size: .85rem;
        margin-bottom: 0;
    }

    .dashboard-role .table thead th {
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: var(--dash-muted);
        background: rgba(56, 65, 74, 0.035);
        white-space: nowrap;
    }

    .dashboard-role .list-group-item {
        padding: .7rem 1.15rem;
        font-size: .85rem;
    }

    /* ---------- QUICK ACTION ---------- */
    .dashboard-role .dash-quick .btn {
        height: 100%;
        border-style: dashed;
        transition: border-color .15s ease, transform .15s ease;
    }

    .dashboard-role .dash-quick .btn:hover {
        transform: translateY(-2px);
        border-style: solid;
    }

    /* ---------- EMPTY STATE (seragam) ---------- */
    .dash-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        gap: .35rem;
        padding: 1.6rem 1rem;
        color: var(--dash-muted);
    }

    .dash-empty i {
        font-size: 1.5rem;
        opacity: .45;
        line-height: 1;
    }

    .dash-empty p {
        margin: 0;
        font-size: .85rem;
        font-weight: 500;
        color: var(--dash-muted);
    }

    .dash-empty small {
        font-size: .75rem;
        color: var(--dash-muted);
        opacity: .8;
    }

    /* ---------- RESPONSIVE ---------- */
    @media (max-width: 991.98px) {
        .welcome-banner { padding: 1.25rem; }
        .welcome-banner .banner-title { font-size: 1.2rem; }
    }

    @media (max-width: 575.98px) {
        .welcome-banner .banner-icon-wrap { display: none; }
        .dash-section-count { display: none; }
        .welcome-banner { padding: 1.1rem; }
    }
</style>
<style>
    /* ---------- HEADER & FILTER DASHBOARD ---------- */
    .dash-header .badge { font-weight: 500; letter-spacing: 0; }
    .dash-kpi { color: inherit; transition: transform .15s ease, box-shadow .15s ease; }
    .dash-kpi:hover { transform: translateY(-2px); box-shadow: 0 .25rem .75rem rgba(56,65,74,.12); }
    .dash-kpi h4 { font-variant-numeric: tabular-nums; }

    /* ---------- CAROUSEL INFO ---------- */
    .info-carousel-card .carousel-inner { border-radius: 0 0 .75rem .75rem; overflow: hidden; }
    .info-slide { position: relative; display: block; color: #fff; }
    .info-slide-image { height: 300px; object-fit: cover; filter: brightness(.72); }
    .info-slide-caption {
        position: absolute; inset: auto 0 0 0;
        padding: 1.25rem 1.5rem;
        background: linear-gradient(180deg, rgba(15,23,42,0) 0%, rgba(15,23,42,.82) 65%);
    }
    .info-slide-caption h4 { text-shadow: 0 1px 2px rgba(0,0,0,.35); }
    @media (max-width: 575.98px) {
        .info-slide-image { height: 220px; }
        .info-slide-caption { padding: .9rem 1rem; }
        .info-slide-caption h4 { font-size: 1rem; }
    }

    /* ---------- MODAL BROADCAST ---------- */
    [data-alim-broadcast-modal] .modal-content { border-radius: .85rem; }
    [data-alim-broadcast-modal] .modal-body p { line-height: 1.7; }
</style>
<style>
    /* ---------- ANIMASI DASHBOARD ---------- */
    @keyframes dashFadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes dashPulseRing {
        0%   { box-shadow: 0 0 0 0 rgba(64, 81, 137, .35); }
        70%  { box-shadow: 0 0 0 10px rgba(64, 81, 137, 0); }
        100% { box-shadow: 0 0 0 0 rgba(64, 81, 137, 0); }
    }
    @keyframes dashSheen {
        0%   { transform: translateX(-120%); }
        100% { transform: translateX(220%); }
    }

    .dash-animate {
        opacity: 0;
        animation: dashFadeUp .55s cubic-bezier(.22, .61, .36, 1) forwards;
    }
    .dash-animate:nth-child(2) { animation-delay: .08s; }
    .dash-animate:nth-child(3) { animation-delay: .16s; }
    .dash-animate:nth-child(4) { animation-delay: .24s; }

    .dash-kpi { position: relative; overflow: hidden; }
    .dash-kpi::after {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(100deg, transparent 20%, rgba(255,255,255,.45) 50%, transparent 80%);
        transform: translateX(-120%);
        pointer-events: none;
    }
    .dash-kpi:hover::after { animation: dashSheen .9s ease; }

    @media (prefers-reduced-motion: reduce) {
        .dash-animate, .dash-kpi::after { animation: none !important; opacity: 1 !important; }
    }

    /* ---------- KALDIK ---------- */
    .kaldik-row:last-child { border-bottom: 0 !important; }
    .kaldik-dot {
        width: 10px; height: 10px; border-radius: 50%;
        margin-top: 6px; flex-shrink: 0;
        box-shadow: 0 0 0 3px rgba(64,81,137,.12);
    }

    /* ---------- JADWAL SHALAT ---------- */
    .prayer-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(88px, 1fr));
        gap: .5rem;
    }
    .prayer-item {
        border: 1px solid var(--dash-border, rgba(56,65,74,.12));
        border-radius: .65rem;
        padding: .65rem .5rem;
        text-align: center;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        background: var(--vz-card-bg, #fff);
    }
    .prayer-item:hover { transform: translateY(-2px); box-shadow: 0 .2rem .6rem rgba(56,65,74,.12); }
    .prayer-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: var(--vz-secondary-color); }
    .prayer-time { font-size: 1.1rem; font-weight: 600; font-variant-numeric: tabular-nums; }
    .prayer-item.is-past { opacity: .55; }
    .prayer-item.is-next {
        border-color: #405189;
        background: rgba(64,81,137,.06);
        animation: dashPulseRing 2.2s ease-out infinite;
    }

    /* ---------- BANNER SALAM ---------- */
    .banner-name { font-weight: 700; }
</style>
