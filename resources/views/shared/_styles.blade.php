<style>
    /* Konvensi UI ALIM (mengikuti pola gtk/index.blade.php) — dipakai lintas modul daftar/kartu.
       Semua warna memakai variabel Bootstrap agar ikut tema terang/gelap. */
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

    /* Label kecil kartu statistik */
    .stat-label { font-size: 11px; }

    /* Kartu statistik */
    .card-animate { transition: all 0.3s ease; }
    .card-animate:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18); }

    /* Tabel beku */
    .table-freeze th,
    .table-freeze td { vertical-align: middle; }
    .table-freeze thead th {
        position: sticky;
        top: 0;
        z-index: 20;
        font-weight: 600;
        border-bottom: 2px solid var(--bs-border-color);
    }
</style>
