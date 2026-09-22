<x-dashboards.stat-card label="Jam Pelajaran Hari Ini" :value="$jadwalHariIni" icon="ri-time-line" color="primary" />
<x-dashboards.stat-card label="Kelas Berlangsung" :value="$kelasAktif" icon="ri-play-circle-line" color="success" />
<x-dashboards.stat-card label="Siswa Absen di Kelas" :value="$siswaAbsen" icon="ri-user-unfollow-line" color="danger" />
<x-dashboards.stat-card label="Grading Pending" :value="$gradingPending" icon="ri-file-text-line" color="warning" />
