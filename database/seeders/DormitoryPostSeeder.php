<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryActivityLog;
use App\Models\DormitoryActivityTemplate;
use App\Models\DormitoryEmergencyBroadcast;
use App\Models\DormitoryPost;
use App\Models\DormitoryPostResponse;
use App\Models\DormitoryResident;
use App\Models\DormitoryVisitLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * DormitoryPostSeeder
 *
 * Membuat data dummy untuk:
 * 1. Dormitory posts (informasi/pengumuman) + responses
 * 2. Dormitory visit logs (kunjungan visitante)
 * 3. Dormitory activity templates (template kegiatan harian)
 * 4. Dormitory activity logs (log kegiatan)
 * 5. Dormitory emergency broadcasts
 *
 * Run AFTER: DormitoryDataSeeder (needs residents/students)
 */
class DormitoryPostSeeder extends Seeder
{
    private function academicYear(string $name, ?string $semester = null): ?AcademicYear
    {
        $q = AcademicYear::where('name', $name);
        if ($semester) {
            $q->where('semester', $semester);
        }

        return $q->first();
    }

    public function run(): void
    {
        $activeYear = $this->academicYear('2025/2026', 'ganjil');
        $activeYearId = $activeYear?->id;

        $dormPutra = Dormitory::where('code', 'ASR-001')->first();
        $dormPutri = Dormitory::where('code', 'ASR-002')->first();
        $kepalaAsrama = User::where('email', 'kepala.asrama@example.com')->first();
        $adminAsrama = User::where('email', 'admin.asrama@example.com')->first();
        $allUsers = User::where('is_active', true)->get();

        if (! $dormPutra || ! $activeYear) {
            $this->command->error('❌ Dormitory or AcademicYear tidak ditemukan.');

            return;
        }

        $this->command->info('=== DormitoryPostSeeder ===');

        // ── Get residents for visit logs & activity logs ───────────────────
        $putraResidents = DormitoryResident::with('student', 'room')
            ->where('dormitory_id', $dormPutra->id)
            ->where('academic_year_id', $activeYearId)
            ->where('is_active', 1)
            ->get();

        $putriResidents = DormitoryResident::with('student', 'room')
            ->where('dormitory_id', $dormPutri->id)
            ->where('academic_year_id', $activeYearId)
            ->where('is_active', 1)
            ->get();

        $allResidents = $putraResidents->merge($putriResidents);

        // ════════════════════════════════════════════════════════════════════
        // 1. ACTIVITY TEMPLATES
        // ════════════════════════════════════════════════════════════════════

        $this->command->info("\n[1/5] Membuat template kegiatan...");

        $templateSessions = [
            'subuh' => [
                'label' => 'Shubuh Berjamaah',
                'items' => [
                    ['key' => 'shubuh_jamaah', 'label' => 'Shubuh Berjamaah', 'type' => 'check'],
                    ['key' => 'setoran_hafalan', 'label' => 'Setoran Hafalan', 'type' => 'check'],
                    ['key' => 'tadarus_quran', 'label' => 'Tadarus Al-Quran', 'type' => 'check'],
                ],
            ],
            'pagi' => [
                'label' => 'Aktivitas Pagi',
                'items' => [
                    ['key' => 'shalat_dhuha', 'label' => 'Shalat Dhuha Berjamaah', 'type' => 'check'],
                    ['key' => 'senam', 'label' => 'Senam Pagi', 'type' => 'check'],
                    ['key' => 'sarapan', 'label' => 'Sarapan Bersama', 'type' => 'check'],
                ],
            ],
            'siang' => [
                'label' => 'Aktivitas Siang',
                'items' => [
                    ['key' => 'makan_siang', 'label' => 'Makan Siang', 'type' => 'check'],
                    ['key' => 'istirahat', 'label' => 'Istirahat', 'type' => 'check'],
                    ['key' => 'belajar', 'label' => 'Belajar Kelompok', 'type' => 'check'],
                ],
            ],
            'sore' => [
                'label' => 'Aktivitas Sore',
                'items' => [
                    ['key' => 'mandi', 'label' => 'Mandi & Bersiap', 'type' => 'check'],
                    ['key' => 'mengaji', 'label' => 'Mengaji/Musyawarah', 'type' => 'check'],
                    ['key' => 'olahraga', 'label' => 'Olahraga/Berkomunitas', 'type' => 'check'],
                ],
            ],
            'isya' => [
                'label' => 'Shalat Isya & Malam',
                'items' => [
                    ['key' => 'shalat_isya', 'label' => 'Shalat Isya Berjamaah', 'type' => 'check'],
                    ['key' => 'tahajud', 'label' => 'Shalat Tahajud (Sunnah)', 'type' => 'check'],
                    ['key' => 'tilawatil_quran', 'label' => 'Tilawatil Quran Malam', 'type' => 'check'],
                ],
            ],
            'malam' => [
                'label' => 'Kegiatan Malam',
                'items' => [
                    ['key' => 'apel_malam', 'label' => 'Apel Malam', 'type' => 'check'],
                    ['key' => 'pencerahan', 'label' => 'Pencerahan/Murobbi', 'type' => 'check'],
                    ['key' => 'tidur', 'label' => 'Persiapan Tidur', 'type' => 'check'],
                ],
            ],
        ];

        foreach ([$dormPutra, $dormPutri] as $dorm) {
            foreach ($templateSessions as $session => $config) {
                DormitoryActivityTemplate::firstOrCreate(
                    ['dormitory_id' => $dorm->id, 'session' => $session],
                    [
                        'dormitory_id' => $dorm->id,
                        'session' => $session,
                        'activity_items' => $config['items'],
                        'is_active' => true,
                        'notes' => "Template kegiatan {$config['label']} untuk {$dorm->name}",
                    ]
                );
            }
        }

        $this->command->info('  ✅ Template kegiatan dibuat untuk Putra & Putri');

        // ════════════════════════════════════════════════════════════════════
        // 2. POSTS (PENGUMUMAN / INFORMASI)
        // ════════════════════════════════════════════════════════════════════

        $this->command->info("\n[2/5] Membuat pengumuman asrama...");

        $posts = [
            // ── PUTRA ──────────────────────────────────────────────────────
            [
                'dorm' => $dormPutra,
                'category' => 'pengumuman',
                'visibility' => 'umum',
                'needs_response' => false,
                'is_pinned' => true,
                'title' => 'Jadwal Apel Malam Bulan Mei 2026',
                'content' => '<p>Assalamualaikum Warahmatullahi Wabarakatuh.</p>
<p>Berikut jadwal apel malam untuk bulan Mei 2026 di Asrama Putra:</p>
<ul>
<li><strong>Senin-Kamis:</strong> Apel malam pukul 20.30 WITA</li>
<li><strong>Jumat:</strong> Apel malam pukul 19.00 WITA</li>
<li><strong>Sabtu:</strong> Apel malam pukul 20.00 WITA</li>
<li><strong>Minggu:</strong> Tidak ada apel malam</li>
</ul>
<p>Semua penghuni wajib hadir tepat waktu. Yang terlambat akan dicatat sebagai alpa.</p>
<p>Wassalamualaikum Warahmatullahi Wabarakatuh.</p>',
            ],
            [
                'dorm' => $dormPutra,
                'category' => 'pengumuman',
                'visibility' => 'wali',
                'needs_response' => true,
                'is_pinned' => false,
                'title' => 'Orientasi Wali Santri Baru Tahun Ajaran 2025/2026',
                'content' => '<p>Yth. Bapak/Ibu Wali Santri</p>
<p>Dengan hormat, kami mengundang Bapak/Ibu untuk hadir dalam acara Orientasi Wali Santri Baru yang akan dilaksanakan pada:</p>
<ul>
<li><strong>Tanggal:</strong> Sabtu, 12 Juli 2025</li>
<li><strong>Pukul:</strong> 08.00 - 12.00 WITA</li>
<li><strong>Tempat:</strong> Aula Pondok Abu Hurairah Mataram</li>
</ul>
<p>Topik bahasan:</p>
<ul>
<li>Peraturan asrama terkini</li>
<li>Sistem pembayaran kosong</li>
<li>Jadwal kegiatan harian asrama</li>
<li>Syarat dan ketentuan penghunian</li>
</ul>
<p>Kehadiran Bapak/Ibu sangat kami harapkan. Terima kasih.</p>',
            ],
            [
                'dorm' => $dormPutra,
                'category' => 'undangan',
                'visibility' => 'pengurus',
                'needs_response' => true,
                'is_pinned' => false,
                'title' => 'Pembinaan Musyrif & Musyrifah Bulanan',
                'content' => '<p>Undangan untuk semua musyrif dan musyrifah Asrama Putra dan Putri.</p>
<p><strong>Tema:</strong> "Pendampingan Santri Bermasalah"</p>
<p><strong>Tanggal:</strong> Setiap hari Sabtu pertama bulan ini</p>
<p><strong>Waktu:</strong> 09.00 - 13.00 WITA</p>
<p><strong>Tempat:</strong> Ruang Meeting Asrama Putra</p>',
            ],
            [
                'dorm' => $dormPutra,
                'category' => 'laporan',
                'visibility' => 'wali',
                'needs_response' => false,
                'is_pinned' => false,
                'title' => 'Laporan Bulanan Asrama Putra — April 2026',
                'content' => '<p><strong>LAPORAN BULANAN ASRAMA PUTRA</strong></p>
<p><strong>Bulan:</strong> April 2026</p>
<hr>
<h4>1. Jumlah Penghuni</h4>
<p>Total penghuni aktif: 78 orang (3 kamar kosong)</p>
<h4>2. Kehadiran</h4>
<ul>
<li>Hadir: 1.872 record (88%)</li>
<li>Izin: 124 record (6%)</li>
<li>Sakit: 62 record (3%)</li>
<li>Alpa: 30 record (1.4%)</li>
<li>Pulang: 40 record (1.6%)</li>
</ul>
<h4>3. Pelanggaran</h4>
<ul>
<li>Ringan: 15 kasus</li>
<li>Sedang: 5 kasus</li>
<li>Berat: 2 kasus</li>
</ul>
<h4>4. Permohonan Izin</h4>
<p>Total izin yang disetujui: 48 izin</p>
<p><em>Dibuat oleh: Ustadz Fulan (Kepala Asrama)</em></p>',
            ],
            [
                'dorm' => $dormPutra,
                'category' => 'darurat',
                'visibility' => 'umum',
                'needs_response' => false,
                'is_pinned' => false,
                'title' => 'Peringatan Kebakaran — Prosedur Evakuasi',
                'content' => '<p class="text-danger"><strong>⚠️ PERHATIAN — PERINGATAN KEBAKARAN</strong></p>
<p> Baru saja terdeteksi asap dari dapur asrama. <strong>Semua penghuni wajib mengikuti prosedur evakuasi berikut:</strong></p>
<ol>
<li>Batalkan semua aktivitas</li>
<li>Matikan semua peralatan listrik</li>
<li>Keluar kamar dengan tenang via jalur evakuasi yang telah ditentukan</li>
<li>Kumpulkan di lapangan terbuka di depan asrama</li>
<li>Tunggu pengarahan dari musyrif</li>
</ol>
<p><strong>JANGAN GUNAKAN LIFT.</strong> Gunakan tangga darurat.</p>
<p> Nomor darurat: 112 (Polisi) | 113 (Ambulans) | 161 (Pemadam)</p>',
            ],

            // ── PUTRI ──────────────────────────────────────────────────────
            [
                'dorm' => $dormPutri,
                'category' => 'pengumuman',
                'visibility' => 'umum',
                'needs_response' => false,
                'is_pinned' => true,
                'title' => 'Jadwal Piket Kebersihan Kamar — Mei 2026',
                'content' => '<p>Assalamualaikum Warahmatullahi Wabarakatuh.</p>
<p>Berikut jadwal piket kebersihan kamar untuk bulan Mei 2026:</p>
<table border="1" cellpadding="5">
<tr><th>Hari</th><th>Blok</th><th>Kamar</th><th>PJ</th></tr>
<tr><td>Senin</td><td>Blok C</td><td>C-01 s/d C-03</td><td>Santri C-01</td></tr>
<tr><td>Selasa</td><td>Blok C</td><td>C-04 s/d C-06</td><td>Santri C-04</td></tr>
<tr><td>Rabu</td><td>Blok D</td><td>D-01 s/d D-03</td><td>Santri D-01</td></tr>
<tr><td>Kamis</td><td>Blok D</td><td>D-04 s/d D-05</td><td>Santri D-04</td></tr>
<tr><td>Jumat</td><td>Semua Blok</td><td>Semua Kamar</td><td>Semua Santri</td></tr>
</table>
<p>Kesalahan dalam piket akan diberikan sanksi ringan.</p>',
            ],
            [
                'dorm' => $dormPutri,
                'category' => 'undangan',
                'visibility' => 'wali',
                'needs_response' => true,
                'is_pinned' => false,
                'title' => 'Persami (Perkemahan Sederhana) Asrama Putri',
                'content' => '<p>Yth. Bapak/Ibu Wali Santri Putri</p>
<p>Akan diadakan Persami (Perkemahan Sederhana) dengan detail sebagai berikut:</p>
<ul>
<li><strong>Tanggal:</strong> 20-21 Juni 2026</li>
<li><strong>Tempat:</strong> Taman Pondok Abu Hurairah</li>
<li><strong>Peserta:</strong> Semua Santri asrama putri</li>
<li><strong>Persyaratan:</strong> Membawa sleeping bag, Al-Quran, dan air minum</li>
</ul>
<p>Konfirmasi kehadiran melalui link berikut: <a href="#">[LINK]</a></p>
<p><em>NB: Yang memiliki surat dokter diperbolehkan tidak ikut serta.</em></p>',
            ],
            [
                'dorm' => $dormPutri,
                'category' => 'pengumuman',
                'visibility' => 'pengurus',
                'needs_response' => false,
                'is_pinned' => false,
                'title' => 'Pemeriksaan Kamar Tahunan — Juni 2026',
                'content' => '<p>Akan dilakukan pemeriksaan kamar secara berkala pada:</p>
<ul>
<li><strong>Tanggal:</strong> Setiap hari Sabtu, 14.00 WITA</li>
<li><strong>Yang memeriksa:</strong> Musyrifah dan Admin Asrama</li>
<li><strong>Yang diperiksa:</strong> Semua kamar asrama putri</li>
</ul>
<p>Standar pemeriksaan:</p>
<ul>
<li>Kebersihan kamar (40%)</li>
<li>Inventaris & kondisi barang (30%)</li>
<li>Kerapihan & kerapihan tempat tidur (30%)</li>
</ul>
<p>Kamar terbaik akan mendapatkan penghargaan.</p>',
            ],
        ];

        $postCount = 0;
        $responseCount = 0;

        foreach ($posts as $p) {
            $creator = $kepalaAsrama ?? $adminAsrama ?? $allUsers->first();
            $post = DormitoryPost::create([
                'id' => (string) Str::uuid(),
                'dormitory_id' => $p['dorm']->id,
                'title' => $p['title'],
                'content' => $p['content'],
                'category' => $p['category'],
                'visibility' => $p['visibility'],
                'needs_response' => $p['needs_response'],
                'is_pinned' => $p['is_pinned'],
                'is_active' => true,
                'attachment_path' => null,
                'created_by' => $creator?->id,
            ]);
            $postCount++;

            // Add responses for posts that need response
            if ($p['needs_response']) {
                $dormResidents = $p['dorm']->id === $dormPutra->id ? $putraResidents : $putriResidents;
                $responseTypes = ['ack', 'ack', 'ack', 'question', 'complaint'];
                $responseMessages = [
                    'ack' => [
                        'InsyaAllah hadir.',
                        'Baik, saya akan hadir.',
                        'Terima kasih atas informasinya.',
                    ],
                    'question' => [
                        'Apakah anak saya boleh membawa obat pribadi?',
                        'Jam berapa drop anak di asrama?',
                    ],
                    'complaint' => [
                        'Mohon informasi jadwal kegiatan bisa dikirim via WhatsApp juga.',
                    ],
                ];

                $numResponses = min($dormResidents->count(), rand(5, 10));
                $respondents = $dormResidents->random($numResponses);

                foreach ($respondents as $resident) {
                    $respType = $responseTypes[array_rand($responseTypes)];
                    DormitoryPostResponse::create([
                        'id' => (string) Str::uuid(),
                        'post_id' => $post->id,
                        'student_id' => $resident->student_id,
                        'parent_name' => $resident->student?->father_name ?? 'Bapak/Wali',
                        'response_type' => $respType,
                        'message' => $responseMessages[$respType][array_rand($responseMessages[$respType])],
                    ]);
                    $responseCount++;
                }
            }
        }

        $this->command->info("  ✅ {$postCount} pengumuman dibuat");
        $this->command->info("  ✅ {$responseCount} response pengumuman dibuat");

        // ════════════════════════════════════════════════════════════════════
        // 3. VISIT LOGS (KUNJUNGAN)
        // ════════════════════════════════════════════════════════════════════

        $this->command->info("\n[3/5] Membuat log kunjungan...");

        $visitorNames = [
            'Bapak H. Abdul Rahman', 'Ibu Hj. Aminah', 'Bapak H. Muhammad Yusuf',
            'Ibu Hj. Fatimah', 'Bapak H. Abdullah', 'Ibu Hj. Salmah',
            'Bapak H. Hasan Basri', 'Ibu Hj. Nurhaliza', 'Bapak H. Ibrahim',
            'Ibu Hj. Zainab', 'Bapak H. Zubair', 'Ibu Hj. Aisyah',
        ];

        $relationships = ['mahrom', 'wali', 'keluarga', 'mahrom', 'wali'];
        $purposes = ['menjenguk', 'bawa_bantuan', 'antar_jemput', 'menjenguk', 'pertemuan_wali'];
        $visitStatuses = ['approved', 'approved', 'arrived', 'checked_out', 'checked_out'];

        $visitCount = 0;
        $allStudentIds = $allResidents->pluck('student_id')->toArray();

        foreach ($allStudentIds as $studentId) {
            $resident = $allResidents->firstWhere('student_id', $studentId);
            if (! $resident) {
                continue;
            }

            // 1-2 visit records per student (some may not have visits)
            $numVisits = rand(0, 2);

            for ($v = 0; $v < $numVisits; $v++) {
                $visitorName = $visitorNames[array_rand($visitorNames)];
                $relationship = $relationships[array_rand($relationships)];
                $purpose = $purposes[array_rand($purposes)];
                $status = $visitStatuses[array_rand($visitStatuses)];
                $expectedArrival = now()->subDays(rand(5, 60))->setTime(rand(9, 16), rand(0, 59));

                $visitLog = DormitoryVisitLog::create([
                    'id' => (string) Str::uuid(),
                    'dormitory_id' => $resident->dormitory_id,
                    'room_id' => $resident->room_id,
                    'student_id' => $studentId,
                    'visitor_name' => $visitorName,
                    'visitor_id_number' => '52'.rand(1000, 9999).rand(1000, 9999),
                    'visitor_phone' => '08'.rand(1000, 9999).rand(1000, 9999),
                    'visitor_relationship' => $relationship,
                    'purpose' => $purpose,
                    'expected_arrival_datetime' => $expectedArrival,
                    'actual_arrival_datetime' => in_array($status, ['arrived', 'checked_out', 'approved']) ? (clone $expectedArrival)->addMinutes(rand(5, 30)) : null,
                    'departure_datetime' => ($status === 'checked_out') ? (clone $expectedArrival)->addHours(2) : null,
                    'expected_meet_duration_minutes' => rand(30, 120),
                    'notes' => "Kunjungan {$purpose} oleh {$relationship}",
                    'approved_by' => ($status !== 'pending') ? ($adminAsrama?->id) : null,
                    'approved_at' => ($status !== 'pending') ? now()->subDays(rand(1, 5)) : null,
                    'approval_note' => null,
                    'check_in_at' => ($status === 'arrived' || $status === 'checked_out') ? now()->subDays(rand(1, 3)) : null,
                    'check_out_at' => ($status === 'checked_out') ? now()->subDays(rand(0, 1)) : null,
                    'status' => $status,
                    'created_by' => $adminAsrama?->id ?? $kepalaAsrama?->id,
                ]);
                $visitCount++;
            }
        }

        $this->command->info("  ✅ {$visitCount} log kunjungan dibuat");

        // ════════════════════════════════════════════════════════════════════
        // 4. ACTIVITY LOGS (LOG KEGIATAN)
        // ════════════════════════════════════════════════════════════════════

        $this->command->info("\n[4/5] Membuat log kegiatan harian...");

        $activityDates = [];
        for ($d = 1; $d <= 5; $d++) {
            $activityDates[] = '2026-05-'.str_pad($d, 2, '0', STR_PAD_LEFT);
        }

        $logCount = 0;
        $sessions = ['subuh', 'pagi', 'siang', 'sore', 'isya', 'malam'];
        $activityTemplates = [
            'subuh' => ['shubuh_jamaah' => true, 'setoran_hafalan' => true, 'tadarus_quran' => true],
            'pagi' => ['shalat_dhuha' => true, 'senam' => true, 'sarapan' => true],
            'siang' => ['makan_siang' => true, 'istirahat' => true, 'belajar' => true],
            'sore' => ['mandi' => true, 'mengaji' => true, 'olahraga' => true],
            'isya' => ['shalat_isya' => true, 'tahajud' => true, 'tilawatil_quran' => true],
            'malam' => ['apel_malam' => true, 'pencerahan' => true, 'tidur' => false],
        ];

        foreach ($allResidents as $resident) {
            $dorm = $resident->dormitory_id;

            foreach ($activityDates as $date) {
                foreach ($sessions as $session) {
                    // 90% chance of log entry
                    if (rand(1, 100) > 90) {
                        continue;
                    }

                    $existing = DormitoryActivityLog::where('resident_id', $resident->id)
                        ->where('activity_date', $date)
                        ->where('session', $session)
                        ->exists();
                    if ($existing) {
                        continue;
                    }

                    $data = $activityTemplates[$session] ?? [];
                    $filledData = [];
                    foreach ($data as $key => $val) {
                        $filledData[$key] = rand(0, 1) === 1 ? $val : false;
                    }

                    DormitoryActivityLog::create([
                        'id' => (string) Str::uuid(),
                        'resident_id' => $resident->id,
                        'dormitory_id' => $dorm,
                        'academic_year_id' => $activeYearId,
                        'activity_date' => $date,
                        'session' => $session,
                        'data' => $filledData,
                        'notes' => null,
                        'notify_parent' => false,
                        'recorded_by' => $adminAsrama?->id ?? $kepalaAsrama?->id,
                    ]);
                    $logCount++;
                }
            }
        }

        $this->command->info("  ✅ {$logCount} log kegiatan harian dibuat");

        // ════════════════════════════════════════════════════════════════════
        // 5. EMERGENCY BROADCASTS
        // ════════════════════════════════════════════════════════════════════

        $this->command->info("\n[5/5] Membuat broadcast darurat...");

        $broadcasts = [
            [
                'dorm' => $dormPutra,
                'title' => 'Peringatan DBD — Fogging Akan Dilakukan',
                'content' => 'Mohon perhatian. Dalam rangka pencegahan DBD, akan dilakukan fogging/pengasapan di seluruh area asrama putra pada hari Sabtu, 7 Mei 2026 pukul 07.00 - 09.00 WITA. Semua Santri diminta membersihkan makanan dan minuman dari kamar. Fogging tidak berbahaya bagi kesehatan.',
                'broadcast_via' => 'all',
                'severity' => 'warning',
                'ack_required' => false,
                'expires_at' => now()->addDays(7),
            ],
            [
                'dorm' => $dormPutri,
                'title' => 'Jadwal Pemadaman Listrik — Perbaikan Jaringan',
                'content' => 'Akan dilakukan perbaikan jaringan listrik di area asrama putri. Pemadaman dijadwalkan hari Minggu, 8 Mei 2026 pukul 13.00 - 16.00 WITA. Semua Santri agar menyiapkan lampu darurat dan mengisi daya HP sebelum pemadaman.',
                'broadcast_via' => 'all',
                'severity' => 'info',
                'ack_required' => false,
                'expires_at' => now()->addDays(3),
            ],
            [
                'dorm' => $dormPutra,
                'title' => 'Orientasi Tahun Ajaran Baru 2026/2027',
                'content' => 'Pengumuman penting untuk semua Santri dan wali. Orientasi tahun ajaran baru akan dimulai tanggal 13 Juli 2026. Santri baru wajib hadir lebih awal pada 10 Juli 2026 untuk proses check-in dan orientasi asrama.',
                'broadcast_via' => 'all',
                'severity' => 'info',
                'ack_required' => true,
                'expires_at' => now()->addDays(60),
            ],
        ];

        $bcCount = 0;
        foreach ($broadcasts as $bc) {
            $creator = $kepalaAsrama ?? $adminAsrama ?? $allUsers->first();
            DormitoryEmergencyBroadcast::create([
                'id' => (string) Str::uuid(),
                'dormitory_id' => $bc['dorm']->id,
                'title' => $bc['title'],
                'content' => $bc['content'],
                'severity' => $bc['severity'],
                'broadcast_via' => $bc['broadcast_via'],
                'ack_required' => $bc['ack_required'],
                'expires_at' => $bc['expires_at'],
                'created_by' => $creator?->id,
            ]);
            $bcCount++;
        }

        $this->command->info("  ✅ {$bcCount} broadcast darurat dibuat");

        // ════════════════════════════════════════════════════════════════════
        // DONE
        // ════════════════════════════════════════════════════════════════════

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('✅ DormitoryPostSeeder selesai!');
        $this->command->info('─────────────────────────���─────────────────────────');
        $this->command->info('  Template Kegiatan : '.(count($templateSessions) * 2).' template');
        $this->command->info("  Pengumuman        : {$postCount} pengumuman");
        $this->command->info("  Response          : {$responseCount} response");
        $this->command->info("  Log Kunjungan     : {$visitCount} kunjungan");
        $this->command->info("  Log Kegiatan      : {$logCount} log");
        $this->command->info("  Broadcast Darurat : {$bcCount} broadcast");
        $this->command->info('═══════════════════════════════════════════════════');
    }
}
