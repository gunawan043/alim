# Usulan Skema — 5 Role Tanpa Data

**Tanggal:** 8 Oktober 2026
**Konteks:** Dashboard role sudah dibangun untuk 9 role dengan data nyata. 5 role berikut belum memiliki tabel sama sekali di skema, sehingga dashboard-nya belum bisa diisi (prinsip: *no dummy data*). Dokumen ini berisi usulan tabel dalam format migration Laravel, relasi ke tabel existing, enum, index, dan peta widget yang akan aktif begitu migrasi dijalankan.

**Role yang dibahas:**
1. Departemen Bahasa
2. Perpustakaan
3. Satuan Keamanan
4. Teknologi Informasi
5. Unit Pelayanan Gizi

**Konvensi yang dipakai (mengikuti skema existing):**
- Primary key `uuid` untuk tabel domain (`$table->uuid('id')->primary()`), kecuali tabel log/transaksi berat boleh `id()`.
- FK: `foreignUuid('school_id')->constrained('schools')`, `user_id → users`, `student_id → students`, `academic_year_id → academic_years`, `work_unit_id → work_units`.
- Enum ditulis sebagai `string` + komentar nilai valid (lebih aman untuk ALTER dibanding native enum), kecuali butuh ketat.
- Setiap tabel: `timestamps()`, dan `softDeletes()` bila data perlu audit (transaksi/pelanggaran).
- Index komposit untuk kolom filter dashboard: `(school_id, tanggal)`, `(status)`, dll.

---

## 1. Departemen Bahasa

Fungsi: jadwal mufrodat, pencatatan pelanggaran bahasa, mahkamah bahasa, ujian lisan.

### 1.1 `language_vocabularies` — master mufrodat
```php
Schema::create('language_vocabularies', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('school_id')->constrained('schools');
    $table->string('language');            // enum: arab | inggris
    $table->string('word');                // kosakata
    $table->string('meaning');             // arti
    $table->string('category')->nullable(); // benda, kerja, sifat, istilah
    $table->string('level')->nullable();   // dasar | menengah | lanjut
    $table->string('week_number')->nullable(); // pekan ke- (untuk jadwal mingguan)
    $table->boolean('is_active')->default(true);
    $table->foreignUuid('created_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->softDeletes();
    $table->index(['school_id', 'language', 'is_active']);
});
```

### 1.2 `language_vocab_schedules` — jadwal mufrodat harian/mingguan
```php
Schema::create('language_vocab_schedules', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('school_id')->constrained('schools');
    $table->foreignUuid('academic_year_id')->nullable()->constrained('academic_years');
    $table->date('schedule_date');
    $table->string('session');             // pagi | siang | sore | malam
    $table->string('scope_type')->nullable(); // sekolah | tingkat | kelas
    $table->string('scope_ref')->nullable();  // grade_level_id / study_group_id
    $table->foreignUuid('vocabulary_id')->nullable()->constrained('language_vocabularies');
    $table->foreignUuid('supervisor_id')->nullable()->constrained('users');
    $table->boolean('is_active')->default(true);
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->index(['school_id', 'schedule_date']);
});
```

### 1.3 `language_violations` — pencatatan pelanggaran bahasa
```php
Schema::create('language_violations', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('school_id')->constrained('schools');
    $table->foreignUuid('student_id')->constrained('students');
    $table->foreignUuid('academic_year_id')->nullable()->constrained('academic_years');
    $table->date('violation_date');
    $table->string('language');             // arab | inggris
    $table->string('category');             // tidak berbahasa resmi | kosakata salah | lupa mufrodat | lainnya
    $table->text('description')->nullable();
    $table->integer('points')->default(0);
    $table->string('sanction_type')->nullable(); // teguran | catat | mahkamah
    $table->string('status')->default('pending'); // pending | mahkamah | selesai
    $table->foreignUuid('recorded_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->softDeletes();
    $table->index(['school_id', 'violation_date']);
    $table->index(['student_id', 'status']);
});
```

### 1.4 `language_court_sessions` — sidang mahkamah bahasa
```php
Schema::create('language_court_sessions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('school_id')->constrained('schools');
    $table->foreignUuid('academic_year_id')->nullable()->constrained('academic_years');
    $table->date('session_date');
    $table->time('session_time')->nullable();
    $table->string('location')->nullable();
    $table->foreignUuid('judge_id')->nullable()->constrained('users');
    $table->foreignUuid('secretary_id')->nullable()->constrained('users');
    $table->string('status')->default('draft'); // draft | terjadwal | berlangsung | selesai
    $table->text('notes')->nullable();
    $table->foreignUuid('created_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->index(['school_id', 'session_date', 'status']);
});
```

### 1.5 `language_court_cases` — perkara yang disidangkan
```php
Schema::create('language_court_cases', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('court_session_id')->constrained('language_court_sessions')->cascadeOnDelete();
    $table->foreignUuid('violation_id')->nullable()->constrained('language_violations');
    $table->foreignUuid('student_id')->constrained('students');
    $table->text('verdict')->nullable();    // putusan
    $table->string('sanction')->nullable(); // sanksi akhir
    $table->integer('points_final')->default(0);
    $table->string('status')->default('menunggu'); // menunggu | diputus | selesai
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->index(['court_session_id', 'status']);
});
```

### 1.6 `language_speaking_assessments` — ujian lisan
```php
Schema::create('language_speaking_assessments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('school_id')->constrained('schools');
    $table->foreignUuid('student_id')->constrained('students');
    $table->foreignUuid('academic_year_id')->nullable()->constrained('academic_years');
    $table->foreignUuid('examiner_id')->nullable()->constrained('users');
    $table->date('assessment_date');
    $table->string('assessment_type');      // mufrodat | muhadatsah | qiroah | insya
    $table->string('language');             // arab | inggris
    $table->decimal('score_fluency', 5, 2)->nullable();
    $table->decimal('score_vocab', 5, 2)->nullable();
    $table->decimal('score_grammar', 5, 2)->nullable();
    $table->decimal('final_score', 5, 2)->nullable();
    $table->string('predicate')->nullable(); // mumtaz | jayyid_jiddan | jayyid | maqbul | rasib
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->index(['school_id', 'assessment_date']);
    $table->index(['student_id']);
});
```

**Widget yang aktif setelah migrasi:** Jadwal Mufrodat Hari Ini · Ujian Lisan Bulan Ini · Pelanggaran Bahasa Bulan Ini (+poin) · Mahkamah Terjadwal · Chart Pelanggaran per Kategori · Chart Nilai Ujian per Jenis · Tabel Jadwal/ Ujian/ Pelanggaran/ Sidang · Quick action Bahasa.

---

## 2. Perpustakaan

Fungsi: sirkulasi, katalog, anggota, denda, stock opname.

### 2.1 `library_categories`
```php
Schema::create('library_categories', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('school_id')->constrained('schools');
    $table->string('code')->nullable();
    $table->string('name');
    $table->string('shelf_location')->nullable(); // rak
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

### 2.2 `library_books` — katalog
```php
Schema::create('library_books', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('school_id')->constrained('schools');
    $table->foreignUuid('category_id')->nullable()->constrained('library_categories');
    $table->string('isbn')->nullable();
    $table->string('title');
    $table->string('author')->nullable();
    $table->string('publisher')->nullable();
    $table->year('publish_year')->nullable();
    $table->string('language')->nullable();
    $table->integer('total_copies')->default(0);
    $table->integer('available_copies')->default(0);
    $table->string('shelf_code')->nullable();
    $table->string('cover_path')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
    $table->index(['school_id', 'title']);
});
```

### 2.3 `library_members` — anggota (santri/GTK/umum)
```php
Schema::create('library_members', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('school_id')->constrained('schools');
    $table->string('member_no')->unique();
    $table->string('member_type');          // santri | gtk | umum
    $table->foreignUuid('student_id')->nullable()->constrained('students');
    $table->foreignUuid('user_id')->nullable()->constrained('users');
    $table->date('join_date')->nullable();
    $table->date('end_date')->nullable();
    $table->string('status')->default('aktif'); // aktif | nonaktif
    $table->timestamps();
    $table->index(['school_id', 'member_type', 'status']);
});
```

### 2.4 `library_loans` — sirkulasi
```php
Schema::create('library_loans', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('school_id')->constrained('schools');
    $table->foreignUuid('book_id')->constrained('library_books');
    $table->foreignUuid('member_id')->constrained('library_members');
    $table->date('loan_date');
    $table->date('due_date');
    $table->date('return_date')->nullable();
    $table->string('status')->default('dipinjam'); // dipinjam | kembali | terlambat | hilang
    $table->integer('renewal_count')->default(0);
    $table->decimal('fine_amount', 12, 2)->default(0);
    $table->boolean('fine_paid')->default(false);
    $table->foreignUuid('processed_by')->nullable()->constrained('users');
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->index(['school_id', 'status', 'due_date']);
});
```

### 2.5 `library_stock_opname_sessions` + `library_stock_opname_items`
```php
Schema::create('library_stock_opname_sessions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('school_id')->constrained('schools');
    $table->string('title');
    $table->date('scheduled_date')->nullable();
    $table->date('closed_date')->nullable();
    $table->string('status')->default('draft'); // draft | berjalan | selesai
    $table->foreignUuid('created_by')->nullable()->constrained('users');
    $table->timestamps();
});

Schema::create('library_stock_opname_items', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('session_id')->constrained('library_stock_opname_sessions')->cascadeOnDelete();
    $table->foreignUuid('book_id')->constrained('library_books');
    $table->integer('system_copies')->default(0);
    $table->integer('found_copies')->default(0);
    $table->integer('missing_copies')->default(0);
    $table->string('condition_before')->nullable();
    $table->string('condition_after')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

**Widget yang aktif:** Total Buku · Buku Dipinjam · Terlambat (overdue) · Anggota Aktif · Denda Bulan Ini · Chart Sirkulasi 7 Hari · Chart Kategori Buku · Tabel Sirkulasi Terbaru · Katalog Terbaru · Stock Opname · Quick action Perpustakaan.

---

## 3. Satuan Keamanan

Fungsi: pos gerbang, log tamu/kendaraan, verifikasi izin outing, patroli, log insiden.

### 3.1 `security_posts` — pos jaga
```php
Schema::create('security_posts', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('school_id')->nullable()->constrained('schools');
    $table->foreignUuid('work_unit_id')->nullable()->constrained('work_units');
    $table->string('code')->nullable();
    $table->string('name');                 // Pos Gerbang Utama, Pos Belakang
    $table->string('location')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

### 3.2 `security_shifts` — jadwal & serah terima jaga
```php
Schema::create('security_shifts', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('post_id')->constrained('security_posts');
    $table->date('shift_date');
    $table->string('shift_type');           // pagi | siang | malam
    $table->foreignUuid('officer_id')->constrained('users');
    $table->timestamp('check_in_at')->nullable();
    $table->timestamp('check_out_at')->nullable();
    $table->string('status')->default('terjadwal'); // terjadwal | berlangsung | selesai | tidak_hadir
    $table->text('handover_notes')->nullable();
    $table->timestamps();
    $table->index(['post_id', 'shift_date', 'shift_type']);
});
```

### 3.3 `security_patrol_logs` — patroli ronda
```php
Schema::create('security_patrol_logs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('post_id')->nullable()->constrained('security_posts');
    $table->foreignUuid('guard_id')->constrained('users');
    $table->timestamp('patrol_start');
    $table->timestamp('patrol_end')->nullable();
    $table->string('route')->nullable();
    $table->text('findings')->nullable();
    $table->text('action_taken')->nullable();
    $table->string('photo_path')->nullable();
    $table->timestamps();
    $table->index(['post_id', 'patrol_start']);
});
```

### 3.4 `security_guest_logs` — log tamu
```php
Schema::create('security_guest_logs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('school_id')->nullable()->constrained('schools');
    $table->foreignUuid('post_id')->nullable()->constrained('security_posts');
    $table->string('guest_name');
    $table->string('guest_id_number')->nullable();
    $table->string('phone')->nullable();
    $table->string('institution')->nullable();
    $table->string('purpose')->nullable();
    $table->string('person_to_meet')->nullable();
    $table->timestamp('check_in_at')->nullable();
    $table->timestamp('check_out_at')->nullable();
    $table->string('vehicle_plate')->nullable();
    $table->text('belongings')->nullable();
    $table->foreignUuid('recorded_by')->nullable()->constrained('users');
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->index(['school_id', 'check_in_at']);
});
```

### 3.5 `security_vehicle_logs` — kendaraan keluar/masuk
```php
Schema::create('security_vehicle_logs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('school_id')->nullable()->constrained('schools');
    $table->foreignUuid('post_id')->nullable()->constrained('security_posts');
    $table->string('vehicle_type')->nullable(); // mobil | motor | bus | truk
    $table->string('plate_number');
    $table->string('driver_name')->nullable();
    $table->string('destination')->nullable();
    $table->timestamp('departure_at')->nullable();
    $table->timestamp('return_at')->nullable();
    $table->foreignUuid('escort_id')->nullable()->constrained('users');
    $table->string('status')->default('keluar'); // keluar | kembali
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->index(['school_id', 'departure_at']);
});
```

### 3.6 `security_incidents` — log insiden
```php
Schema::create('security_incidents', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('school_id')->nullable()->constrained('schools');
    $table->dateTime('incident_at');
    $table->string('incident_type');        // kehilangan | kerusakan | keributan | medis | kebakaran | lainnya
    $table->string('location')->nullable();
    $table->text('description');
    $table->string('severity')->default('ringan'); // ringan | sedang | berat
    $table->text('action_taken')->nullable();
    $table->foreignUuid('handled_by')->nullable()->constrained('users');
    $table->string('status')->default('open'); // open | proses | selesai
    $table->timestamps();
    $table->softDeletes();
    $table->index(['school_id', 'incident_at']);
});
```

### 3.7 `security_permit_verifications` — verifikasi izin outing (integrasi `dormitory_permits`)
```php
Schema::create('security_permit_verifications', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('dormitory_permit_id')->constrained('dormitory_permits')->cascadeOnDelete();
    $table->foreignUuid('post_id')->nullable()->constrained('security_posts');
    $table->foreignUuid('verified_by')->constrained('users');
    $table->timestamp('verified_at');
    $table->string('verification_type');    // keluar | kembali
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

**Widget yang aktif:** Tamu Hari Ini · Kendaraan Keluar · Pos Aktif · Insiden Bulan Ini · Jadwal Jaga Hari Ini · Chart Tamu 7 Hari · Chart Insiden per Jenis · Tabel Tamu/Kendaraan/Insiden/Patroli · Verifikasi Izin · Quick action Keamanan.

---

## 4. Teknologi Informasi

Fungsi: infrastructure management, pemeliharaan server/jaringan, akun user, backup data, problem perangkat TI.

### 4.1 `it_devices` — perangkat
```php
Schema::create('it_devices', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('school_id')->nullable()->constrained('schools');
    $table->foreignUuid('work_unit_id')->nullable()->constrained('work_units');
    $table->foreignUuid('asset_id')->nullable()->constrained('assets'); // link ke master aset
    $table->string('device_type');          // server | router | switch | access_point | cctv | komputer | printer | lainnya
    $table->string('brand')->nullable();
    $table->string('model')->nullable();
    $table->string('serial_number')->nullable();
    $table->string('ip_address')->nullable();
    $table->string('mac_address')->nullable();
    $table->date('acquisition_date')->nullable();
    $table->date('warranty_end')->nullable();
    $table->string('status')->default('aktif'); // aktif | maintenance | rusak | retired
    $table->string('location')->nullable();
    $table->foreignUuid('custodian_id')->nullable()->constrained('users');
    $table->timestamps();
    $table->softDeletes();
    $table->index(['school_id', 'device_type', 'status']);
});
```

### 4.2 `it_backups` + `it_backup_schedules`
```php
Schema::create('it_backups', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('backup_type');          // database | files | config
    $table->string('target')->nullable();   // path / bucket
    $table->decimal('size_mb', 12, 2)->nullable();
    $table->string('checksum')->nullable();
    $table->string('status')->default('proses'); // proses | sukses | gagal | partial
    $table->timestamp('started_at')->nullable();
    $table->timestamp('finished_at')->nullable();
    $table->foreignUuid('performed_by')->nullable()->constrained('users');
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->index(['backup_type', 'status', 'started_at']);
});

Schema::create('it_backup_schedules', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');
    $table->string('backup_type');
    $table->string('frequency');            // harian | mingguan | bulanan
    $table->time('run_at')->nullable();
    $table->timestamp('last_run_at')->nullable();
    $table->timestamp('next_run_at')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

### 4.3 `it_network_monitorings` — log server/jaringan
```php
Schema::create('it_network_monitorings', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('device_id')->constrained('it_devices')->cascadeOnDelete();
    $table->timestamp('checked_at');
    $table->integer('ping_ms')->nullable();
    $table->decimal('packet_loss', 5, 2)->nullable();
    $table->string('status')->default('up'); // up | down | degraded
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->index(['device_id', 'checked_at']);
});
```

### 4.4 `it_helpdesk_tickets` — problem perangkat
```php
Schema::create('it_helpdesk_tickets', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('ticket_no')->unique();
    $table->foreignUuid('school_id')->nullable()->constrained('schools');
    $table->foreignUuid('reporter_id')->constrained('users');
    $table->foreignUuid('device_id')->nullable()->constrained('it_devices');
    $table->string('category');              // hardware | software | jaringan | akun | lainnya
    $table->string('priority')->default('medium'); // low | medium | high | urgent
    $table->string('title');
    $table->text('description')->nullable();
    $table->string('status')->default('open'); // open | assigned | in_progress | resolved | closed
    $table->foreignUuid('assigned_to')->nullable()->constrained('users');
    $table->timestamp('resolved_at')->nullable();
    $table->text('resolution_notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
    $table->index(['school_id', 'status', 'priority']);
});
```

### 4.5 `it_account_requests` — permintaan akun
```php
Schema::create('it_account_requests', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('requester_id')->constrained('users');
    $table->string('request_type');         // create | reset_password | deactivate | role_change
    $table->foreignUuid('target_user_id')->nullable()->constrained('users');
    $table->string('role_requested')->nullable();
    $table->string('status')->default('pending'); // pending | diproses | selesai | ditolak
    $table->foreignUuid('handled_by')->nullable()->constrained('users');
    $table->timestamp('handled_at')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->index(['status']);
});
```

**Widget yang aktif:** Perangkat Aktif · Perangkat Rusak/Maintenance · Backup Terakhir (status) · Ticket Open · SLA Ticket · Uptime/Status Jaringan · Chart Ticket 7 Hari · Chart Ticket per Kategori · Tabel Perangkat · Backup · Ticket · Akun Request · Monitoring Terbaru · Quick action TI.

---

## 5. Unit Pelayanan Gizi

Fungsi: perencanaan siklus menu, stok bahan makanan, jadwal masak/distribusi, kebersihan dapur.

### 5.1 `kitchen_menus` — menu harian
```php
Schema::create('kitchen_menus', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('school_id')->constrained('schools');
    $table->date('menu_date');
    $table->string('meal_type');            // sarapan | makan_siang | makan_malam | snack
    $table->string('name');
    $table->text('description')->nullable();
    $table->decimal('calories_kcal', 8, 2)->nullable();
    $table->decimal('protein_g', 8, 2)->nullable();
    $table->decimal('carbs_g', 8, 2)->nullable();
    $table->decimal('fat_g', 8, 2)->nullable();
    $table->boolean('is_approved')->default(false);
    $table->foreignUuid('approved_by')->nullable()->constrained('users');
    $table->foreignUuid('created_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->index(['school_id', 'menu_date', 'meal_type']);
});
```

### 5.2 `nutrition_cycles` + `nutrition_cycle_items` — siklus menu
```php
Schema::create('nutrition_cycles', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('school_id')->constrained('schools');
    $table->string('name');
    $table->date('start_date');
    $table->date('end_date');
    $table->integer('weeks')->default(4);
    $table->string('status')->default('draft'); // draft | aktif | selesai
    $table->foreignUuid('created_by')->nullable()->constrained('users');
    $table->timestamps();
});

Schema::create('nutrition_cycle_items', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('cycle_id')->constrained('nutrition_cycles')->cascadeOnDelete();
    $table->integer('day_number');          // hari ke- dalam siklus
    $table->string('meal_type');
    $table->string('menu_name');
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->index(['cycle_id', 'day_number']);
});
```

### 5.3 `food_stocks` — stok bahan pangan
```php
Schema::create('food_stocks', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('school_id')->constrained('schools');
    $table->foreignUuid('warehouse_id')->nullable()->constrained('warehouses');
    $table->string('item_name');
    $table->string('category')->nullable(); // karbohidrat | protein | sayur | buah | bumbu | minyak | lainnya
    $table->string('unit')->nullable();     // kg | liter | pcs | karung
    $table->decimal('current_stock', 12, 2)->default(0);
    $table->decimal('min_stock', 12, 2)->default(0);
    $table->date('expiry_date')->nullable();
    $table->decimal('unit_price', 14, 2)->nullable();
    $table->string('supplier')->nullable();
    $table->timestamp('last_checked_at')->nullable();
    $table->foreignUuid('checked_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->index(['school_id', 'category']);
});
```

### 5.4 `food_stock_movements` — keluar/masuk stok
```php
Schema::create('food_stock_movements', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('stock_id')->constrained('food_stocks')->cascadeOnDelete();
    $table->string('movement_type');        // masuk | keluar | penyesuaian | rusak
    $table->decimal('quantity', 12, 2);
    $table->decimal('balance_after', 12, 2)->nullable();
    $table->timestamp('occurred_at');
    $table->string('reason')->nullable();
    $table->foreignUuid('performed_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->index(['stock_id', 'occurred_at']);
});
```

### 5.5 `meal_distributions` — distribusi makanan
```php
Schema::create('meal_distributions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('school_id')->constrained('schools');
    $table->date('distribution_date');
    $table->string('meal_type');
    $table->integer('total_portions')->default(0);
    $table->integer('distributed_portions')->default(0);
    $table->string('destination')->nullable(); // asrama | kelas | panitia | lainnya
    $table->string('status')->default('rencana'); // rencana | berlangsung | selesai
    $table->foreignUuid('distributed_by')->nullable()->constrained('users');
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->index(['school_id', 'distribution_date']);
});
```

### 5.6 `kitchen_sanitation_logs` — kebersihan dapur
```php
Schema::create('kitchen_sanitation_logs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('school_id')->constrained('schools');
    $table->date('log_date');
    $table->string('area');                 // dapur | gudang | ruang cuci | distribusi
    $table->json('checklist')->nullable();  // item kebersihan + status
    $table->text('findings')->nullable();
    $table->text('action_taken')->nullable();
    $table->foreignUuid('checked_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->index(['school_id', 'log_date']);
});
```

**Widget yang aktif:** Menu Hari Ini · Distribusi Hari Ini · Stok Bahan Menipis · Bahan Kadaluarsa · Siklus Menu Aktif · Chart Distribusi 7 Hari · Chart Stok per Kategori · Tabel Menu/ Stok/ Distribusi/ Sanitasi Dapur · Quick action Gizi.

---

## Ringkasan Prioritas Implementasi

| Prioritas | Role | Alasan |
|---|---|---|
| 1 | Perpustakaan | Paling sering diminta; skema sederhana & jelas |
| 2 | Unit Pelayanan Gizi | Operasional harian (menu & distribusi) |
| 3 | Satuan Keamanan | Log tamu & patroli harian |
| 4 | Departemen Bahasa | Perlu master mufrodat + alur mahkamah |
| 5 | Teknologi Informasi | Paling teknis; bisa bertahap (devices → tickets → backup) |

**Setelah migrasi dijalankan:** dashboard untuk 5 role tersebut dapat dibangun dengan pola yang sama seperti 9 role sebelumnya — cukup 1 controller + 1 config + partial per widget (sebagian besar bisa memakai template `_stat`, `_chart`, `_table` yang sudah ada). Tidak ada perubahan pada role yang sudah berjalan.
