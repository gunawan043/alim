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
