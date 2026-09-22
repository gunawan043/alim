<x-dashboards.stat-card label="Santri Aktif" :value="$totalSantri" icon="ri-group-line" color="primary" />
<x-dashboards.stat-card label="GTK Aktif" :value="$totalGtk" icon="ri-user-follow-line" color="success" />
<x-dashboards.stat-card label="Rombel Aktif" :value="$totalRombel" icon="ri-school-line" color="warning" />
<x-dashboards.stat-card label="Kehadiran Hari Ini" :value="$kehadiranHariIni . '%'" icon="ri-checkbox-circle-line" color="info" />
