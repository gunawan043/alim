<style>
    /* Konvensi UI modul Kurikulum — mengikuti pola gtk/index.blade.php.
       Warna memakai variabel Bootstrap agar ikut tema terang/gelap. */
    .filter-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border: 1px solid var(--bs-border-color);
        border-radius: 30px;
        font-size: 13px;
        transition: all 0.2s;
        margin: 4px;
        cursor: pointer;
        color: var(--bs-body-color);
        text-decoration: none;
        background: transparent;
    }

    .filter-badge:hover { background: var(--bs-primary); border-color: var(--bs-primary); color: #fff; }
    .filter-badge.active { background: var(--bs-primary); border-color: var(--bs-primary); color: #fff; }

    .stat-label { font-size: 11px; }

    /* Kartu statistik — mengikuti gtk/index.blade.php */
    .card-animate { transition: all 0.3s ease; }
    .card-animate:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18); }

    .table-freeze th,
    .table-freeze td { vertical-align: middle; }
    .table-freeze thead th {
        position: sticky;
        top: 0;
        z-index: 20;
        font-weight: 600;
        border-bottom: 2px solid var(--bs-border-color);
    }

    /* Alur lengkap kurikulum — kartu bertahap yang dapat diklik */
    .curriculum-flow-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 12px;
    }
    .curriculum-flow-card {
        position: relative;
        display: flex;
        flex-direction: column;
        min-height: 150px;
        padding: 14px;
        border: 1px solid var(--bs-border-color);
        border-radius: 10px;
        background: var(--bs-body-bg);
        color: inherit;
        text-decoration: none;
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }
    .curriculum-flow-card:hover {
        transform: translateY(-3px);
        border-color: var(--bs-primary);
        box-shadow: 0 8px 20px rgba(var(--bs-primary-rgb), .18);
        color: inherit;
    }
    .curriculum-flow-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 10px;
    }
    .curriculum-flow-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: var(--bs-primary);
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        flex: 0 0 auto;
    }
    .curriculum-flow-icon {
        color: var(--bs-primary);
        font-size: 20px;
        line-height: 1;
    }
    .curriculum-flow-title {
        font-weight: 700;
        font-size: 14px;
        line-height: 1.25;
        margin-bottom: 4px;
    }
    .curriculum-flow-desc {
        color: var(--bs-secondary-color);
        font-size: 11px;
        line-height: 1.35;
        flex: 1;
    }
    .curriculum-flow-open {
        color: var(--bs-primary);
        font-size: 11px;
        font-weight: 600;
        margin-top: 10px;
    }
    .curriculum-flow-arrow {
        position: absolute;
        right: -17px;
        top: 50%;
        transform: translateY(-50%);
        z-index: 2;
        color: var(--bs-secondary-color);
        background: var(--bs-body-bg);
        font-size: 15px;
    }
    @media (max-width: 1199.98px) {
        .curriculum-flow-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .curriculum-flow-arrow { display: none; }
    }
    @media (max-width: 575.98px) {
        .curriculum-flow-grid { grid-template-columns: 1fr; }
    }
</style>
