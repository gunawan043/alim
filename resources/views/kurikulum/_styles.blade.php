<style>
    /* Konvensi UI modul Kurikulum — mengikuti pola gtk/index.blade.php */
    .filter-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 30px;
        font-size: 13px;
        transition: all 0.2s;
        margin: 4px;
        cursor: pointer;
        color: inherit;
        text-decoration: none;
        background: #fff;
    }

    .filter-badge:hover { background: #405189; border-color: #94a3b8; color: #fff; }
    .filter-badge.active { background: #0a5f9e; border-color: #0a5f9e; color: #fff; }

    .stat-label { font-size: 11px; }

    /* Kartu statistik — mengikuti gtk/index.blade.php */
    .card-animate { transition: all 0.3s ease; }
    .card-animate:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }

    .table-freeze th,
    .table-freeze td { vertical-align: middle; }
    .table-freeze thead th {
        position: sticky;
        top: 0;
        z-index: 20;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
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
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        color: inherit;
        text-decoration: none;
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }
    .curriculum-flow-card:hover {
        transform: translateY(-3px);
        border-color: #0a5f9e;
        box-shadow: 0 8px 20px rgba(10, 95, 158, .12);
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
        background: #0a5f9e;
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        flex: 0 0 auto;
    }
    .curriculum-flow-icon {
        color: #0a5f9e;
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
        color: #6b7280;
        font-size: 11px;
        line-height: 1.35;
        flex: 1;
    }
    .curriculum-flow-open {
        color: #0a5f9e;
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
        color: #94a3b8;
        background: #fff;
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
