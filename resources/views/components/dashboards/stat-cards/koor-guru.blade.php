<x-dashboards.stat-card label="Guru di Bidang Koor" :value="$totalGuruKoor" icon="ri-team-line" color="primary" />
<x-dashboards.stat-card label="Guru Absen Hari Ini" :value="$guruAbsen" icon="ri-user-unfollow-line" color="danger" />
<x-dashboards.stat-card label="Jam Mengajar Berlangsung" :value="$jadwalBerlangsung" icon="ri-play-circle-line" color="success" />
<x-dashboards.stat-card label="Kehadiran Bulanan" :value="$kehadiranBulanan . '%'" icon="ri-archive-line" color="warning" />
