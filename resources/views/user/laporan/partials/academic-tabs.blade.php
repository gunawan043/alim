@include('user.laporan.partials.tabs', [
    'tabs' => [
        ['route' => 'user.laporan.akademik', 'label' => 'Analisis STS/SAS'],
        ['route' => 'user.laporan.akademik.ketuntasan', 'label' => 'Ketuntasan'],
        ['route' => 'user.laporan.akademik.kehadiran', 'label' => 'Kehadiran santri'],
        ['route' => 'user.laporan.akademik.keterlaksanaan', 'label' => 'Keterlaksanaan'],
        ['route' => 'user.laporan.akademik.administrasi', 'label' => 'Administrasi'],
        ['route' => 'user.laporan.pendidikan.mutu', 'label' => 'Mutu pendidikan'],
    ],
])
