<!-- ========== App Menu ========== -->
<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        <!-- Dark Logo-->
        <a href="{{ route('waka.dashboard') }}" class="logo logo-dark mt-2">
            <span class="logo-sm">
                <img src="{{ URL::asset('build/images/logo-sm.png') }}" alt="" height="60">
            </span>
            <span class="logo-lg">
                <img src="{{ URL::asset('build/images/logo-dark.png') }}" alt="" height="60">
            </span>
        </a>
        <!-- Light Logo-->
        <a href="{{ route('waka.dashboard') }}" class="logo logo-light mt-2">
            <span class="logo-sm">
                <img src="{{ URL::asset('build/images/logo-sm.png') }}" alt="" height="60">
            </span>
            <span class="logo-lg">
                <img src="{{ URL::asset('build/images/logo-light.png') }}" alt="" height="60">
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover"
            id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div id="scrollbar" class="mt-2">
        <div class="container-fluid mt-3">

            <div id="two-column-menu">
            </div>
            <ul class="navbar-nav" id="navbar-nav">
                <!-- Menu Utama -->
                <li class="menu-title"><span>Menu</span></li>

                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('waka.dashboard') }}">
                        <i class="ri-home-6-line"></i> <span>Dashboard</span>
                    </a>
                </li>

                <!-- GTK & Peserta Didik -->
                <li class="menu-title"><span>GTK & Peserta Didik</span></li>

                <!-- GTK -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#GTK" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="GTK">
                        <i class="ri-contacts-book-2-line"></i> <span>Data GTK</span>
                    </a>
                    <div class="collapse menu-dropdown" id="GTK">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('waka.gtk-guru') }}" class="nav-link">Guru</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('waka.gtk-tendik') }}" class="nav-link">Tendik</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Peserta Didik -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#PesertaDidik" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="PesertaDidik">
                        <i class="ri-team-line"></i> <span>Peserta Didik</span>
                    </a>
                    <div class="collapse menu-dropdown" id="PesertaDidik">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('waka.peserta-didik.data-kelas') }}" class="nav-link">Data Kelas</a>
                            </li>
                            <li class="nav-item">
                                <a href="#Rombel" class="nav-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="Rombel">Rombel</a>
                                <div class="collapse menu-dropdown" id="Rombel">
                                    <ul class="nav nav-sm flex-column">
                                        <li class="nav-item"><a href="{{ route('waka.peserta-didik.rombel', ['rombel' => '7a']) }}" class="nav-link">7A</a></li>
                                        <li class="nav-item"><a href="{{ route('waka.peserta-didik.rombel', ['rombel' => '7b']) }}" class="nav-link">7B</a></li>
                                        <li class="nav-item"><a href="{{ route('waka.peserta-didik.rombel', ['rombel' => '7c']) }}" class="nav-link">7C</a></li>
                                    </ul>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('waka.peserta-didik.mutasi') }}" class="nav-link">Mutasi PD</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('waka.peserta-didik.masuk') }}" class="nav-link">PD Masuk</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('waka.peserta-didik.keluar') }}" class="nav-link">PD Keluar</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Poin Pelanggaran -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('waka.poin-pelanggaran') }}">
                        <i class="ri-spam-line"></i> <span>Poin Pelanggaran</span>
                    </a>
                </li>

                <!-- Akademik -->
                <li class="menu-title"><span>Akademik</span></li>

                <!-- Pelaksanaan Sumatif -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#PelaksanaanSumatif" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="PelaksanaanSumatif">
                        <i class="ri-file-edit-line"></i> <span>Pelaksanaan Sumatif</span>
                    </a>
                    <div class="collapse menu-dropdown" id="PelaksanaanSumatif">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('waka.kisi-kisi-soal') }}" class="nav-link">Kisi-Kisi Soal</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('waka.soal-sumatif') }}" class="nav-link">Soal Sumatif</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Data Nilai -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#DataNilai" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="DataNilai">
                        <i class="ri-survey-line"></i> <span>Data Nilai</span>
                    </a>
                    <div class="collapse menu-dropdown" id="DataNilai">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="#NilaiSTS" class="nav-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="NilaiSTS">Nilai STS</a>
                                <div class="collapse menu-dropdown" id="NilaiSTS">
                                    <ul class="nav nav-sm flex-column">
                                        <li class="nav-item"><a href="{{ route('waka.nilai-sts', ['kelas' => '7a']) }}" class="nav-link">7A</a></li>
                                        <li class="nav-item"><a href="{{ route('waka.nilai-sts', ['kelas' => '7b']) }}" class="nav-link">7B</a></li>
                                        <li class="nav-item"><a href="{{ route('waka.nilai-sts', ['kelas' => '7c']) }}" class="nav-link">7C</a></li>
                                    </ul>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a href="#NilaiSAS" class="nav-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="NilaiSAS">Nilai SAS</a>
                                <div class="collapse menu-dropdown" id="NilaiSAS">
                                    <ul class="nav nav-sm flex-column">
                                        <li class="nav-item"><a href="{{ route('waka.nilai-sas', ['kelas' => '7a']) }}" class="nav-link">7A</a></li>
                                        <li class="nav-item"><a href="{{ route('waka.nilai-sas', ['kelas' => '7b']) }}" class="nav-link">7B</a></li>
                                        <li class="nav-item"><a href="{{ route('waka.nilai-sas', ['kelas' => '7c']) }}" class="nav-link">7C</a></li>
                                    </ul>
                                </div>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Absensi -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#Absensi" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="Absensi">
                        <i class="ri-contacts-book-line"></i> <span>Absensi</span>
                    </a>
                    <div class="collapse menu-dropdown" id="Absensi">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('waka.absensi-gtk') }}" class="nav-link">Absensi GTK</a>
                            </li>
                            <li class="nav-item">
                                <a href="#AbsensiPD" class="nav-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="AbsensiPD">Absensi Peserta Didik</a>
                                <div class="collapse menu-dropdown" id="AbsensiPD">
                                    <ul class="nav nav-sm flex-column">
                                        <li class="nav-item"><a href="{{ route('waka.absensi-pd', ['kelas' => '7a']) }}" class="nav-link">7A</a></li>
                                        <li class="nav-item"><a href="{{ route('waka.absensi-pd', ['kelas' => '7b']) }}" class="nav-link">7B</a></li>
                                        <li class="nav-item"><a href="{{ route('waka.absensi-pd', ['kelas' => '7c']) }}" class="nav-link">7C</a></li>
                                    </ul>
                                </div>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Data Prestasi -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#DataPrestasi" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="DataPrestasi">
                        <i class="ri-trophy-line"></i> <span>Data Prestasi</span>
                    </a>
                    <div class="collapse menu-dropdown" id="DataPrestasi">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('waka.prestasi-akademik') }}" class="nav-link">Prestasi Akademik</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('waka.hafalan-quran') }}" class="nav-link">Hafalan Qur'an</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('waka.hafalan-hadits') }}" class="nav-link">Hafalan Hadits</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Ekstrakurikuler -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('waka.ekstrakurikuler') }}">
                        <i class="ri-basketball-line"></i> <span>Ekstrakurikuler</span>
                    </a>
                </li>

                <!-- Supervisi (khusus Waka) -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('waka.supervisi') }}">
                        <i class="ri-file-excel-2-line"></i> <span>Supervisi</span>
                    </a>
                </li>

                <!-- Administrasi -->
                <li class="menu-title"><span>Administrasi</span></li>

                <!-- Jadwal KBM -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#JadwalKBM" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="JadwalKBM">
                        <i class="ri-git-repository-line"></i> <span>Jadwal KBM</span>
                    </a>
                    <div class="collapse menu-dropdown" id="JadwalKBM">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('waka.sk-guru') }}" class="nav-link">SK Guru</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('waka.jadwal-pelajaran') }}" class="nav-link">Jadwal Pelajaran</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('waka.jam-mengajar') }}" class="nav-link">Jam Mengajar</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('waka.rekap-pergantian-jam') }}" class="nav-link">Rekap Pergantian Jam</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Surat Menyurat -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#SuratMenyurat" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="SuratMenyurat">
                        <i class="ri-mail-send-line"></i> <span>Surat Menyurat</span>
                    </a>
                    <div class="collapse menu-dropdown" id="SuratMenyurat">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('waka.surat-keluar') }}" class="nav-link">Surat Keluar</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('waka.surat-masuk') }}" class="nav-link">Surat Masuk</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Dokumen ISO -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('waka.dokumen-iso') }}">
                        <i class="ri-dashboard-2-line"></i> <span>Dokumen ISO</span>
                    </a>
                </li>

                <!-- Pendukung -->
                <li class="menu-title"><span>Pendukung</span></li>

                <!-- Agenda Kegiatan -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#AgendaKegiatan" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="AgendaKegiatan">
                        <i class="ri-calendar-todo-line"></i> <span>Agenda Kegiatan</span>
                    </a>
                    <div class="collapse menu-dropdown" id="AgendaKegiatan">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('waka.kaldik') }}" class="nav-link">Kaldik</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('waka.pekan-efektif') }}" class="nav-link">Pekan Efektif</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Sarana Prasarana -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('waka.sarana-prasarana') }}">
                        <i class="ri-community-line"></i> <span>Sarana Prasarana</span>
                    </a>
                </li>

                <!-- Data Alumni -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('waka.data-alumni') }}">
                        <i class="ri-group-2-line"></i> <span>Data Alumni</span>
                    </a>
                </li>

            </ul>
        </div>
        <!-- Sidebar -->
    </div>
    <div class="sidebar-background"></div>
</div>
<!-- Left Sidebar End -->
<!-- Vertical Overlay-->
<div class="vertical-overlay"></div>
