<x-dashboards.stat-card label="Santri Aktif" :value="$totalSantri" icon="ri-group-line" color="primary" />
<x-dashboards.stat-card label="Pelanggaran Bulan Ini" :value="$pelanggaran" icon="ri-alert-fill" color="danger" />
<x-dashboards.stat-card label="Ekskul Aktif" :value="$ekskulAktif" icon="ri-trophy-line" color="success" />
<x-dashboards.stat-card label="Izin Pulang Pending" :value="$izinPending" icon="ri-file-list-3-line" color="warning" />
