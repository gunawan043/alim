<x-dashboards.stat-card label="Total Santri Kelas" :value="$totalSantri" icon="ri-group-line" color="primary" />
<x-dashboards.stat-card label="Hadir Hari Ini" :value="$hadirHariIni . '%'" icon="ri-checkbox-circle-line" color="success" />
<x-dashboards.stat-card label="Rata-rata Nilai" :value="$rataNilai" icon="ri-bar-chart-line" color="warning" />
<x-dashboards.stat-card label="Perlu Perhatian" :value="$perluPerhatian" icon="ri-alert-fill" color="danger" />
