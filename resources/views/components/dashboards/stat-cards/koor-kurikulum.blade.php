<x-dashboards.stat-card label="Total Mapel" :value="$totalMapel" icon="ri-book-line" color="primary" />
<x-dashboards.stat-card label="Progress Silabus" :value="$progressSilabus . '%'" icon="ri-progress-line" color="success" />
<x-dashboards.stat-card label="Paket Soal Pending Review" :value="$paketSoalPending" icon="ri-file-text-line" color="warning" />
<x-dashboards.stat-card label="Ujian 14 Hari Ke Depan" :value="$ujianMendatang" icon="ri-calendar-check-line" color="info" />
