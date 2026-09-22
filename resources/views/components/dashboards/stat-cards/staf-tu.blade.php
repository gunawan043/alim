<x-dashboards.stat-card label="Surat Masuk Hari Ini" :value="$suratMasuk" icon="ri-mail-open-line" color="primary" />
<x-dashboards.stat-card label="Surat Keluar Hari Ini" :value="$suratKeluar" icon="ri-send-plane-line" color="success" />
<x-dashboards.stat-card label="Dokumen GTK Pending" :value="$dokumenPending" icon="ri-file-warning-line" color="warning" />
<x-dashboards.stat-card label="Agenda TU Hari Ini" :value="$agendaHariIni" icon="ri-calendar-event-line" color="info" />
