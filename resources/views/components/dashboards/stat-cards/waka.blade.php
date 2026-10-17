<x-dashboards.stat-card label="Kelas Diampu" :value="$totalKelas" icon="ri-school-line" color="primary" />
<x-dashboards.stat-card label="Guru Absen Hari Ini" :value="$guruAbsen" icon="ri-user-unfollow-line" color="danger" />
<x-dashboards.stat-card label="Pengajuan GTK Pending" :value="$pengajuanPending" icon="ri-mail-unread-line" color="warning" />
<x-dashboards.stat-card label="Tugas Koordinator Pending" :value="$tugasPending" icon="ri-task-line" color="info" />
