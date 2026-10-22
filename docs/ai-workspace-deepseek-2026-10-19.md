# ALIM AI Workspace — DeepSeek

Tanggal: 19 Oktober 2026

## Keputusan Akses

1. **Data sensitif**
   - Payroll/gaji: hanya agregat, tidak pernah detail.
   - NIK/KK/NPWP/rekening: diblokir total dari jawaban AI.
   - Kesehatan: boleh diakses bila pengguna berwenang dan relevan (modul UKS menyusul di MVP berikutnya).
2. **Ranah Super Admin yang dikecualikan**
   - User, role, permission, pengaturan sistem, provider/API key, integrasi, audit log.
3. **Akses halaman**
   - Semua pegawai dapat membuka `/ai`.
   - Cakupan data mengikuti izin dan kelas yang diampu.
   - Pimpinan/Kepala Satuan mendapat cakupan sekolah/lintas sekolah.
4. **MVP**
   - Santri, GTK, rombel/mapel, presensi, nilai/rapor, tahfidz, LJK.
   - Model default `deepseek-flash`, analisis berat `deepseek-v4-pro`.

## Arsitektur

### Provider
- DeepSeek API resmi: `https://api.deepseek.com/chat/completions`.
- Preset dan katalog provider sudah tersedia di Pengaturan AI.
- API key disimpan terenkripsi di `ai_provider_settings`.
- Fallback ENV: `DEEPSEEK_API_KEY`, `DEEPSEEK_ENDPOINT`, `DEEPSEEK_FLASH_MODEL`, `DEEPSEEK_PRO_MODEL`.
- Urutan provider:
  1. Satu-satunya provider yang aktif di Pengaturan AI.
  2. Fallback ENV hanya dipakai jika belum ada provider aktif di database.
- Mengaktifkan provider lain otomatis menonaktifkan provider sebelumnya; hanya boleh ada satu provider aktif.
- Kualitas `flash`/`pro` mengatur batas token dan timeout; provider/model tetap mengikuti provider aktif yang dipilih di Pengaturan AI.

### Halaman
- `/{userId}/ai` — landing + chat + riwayat.
- `/{userId}/ai/stream` — SSE streaming jawaban.
- Halaman memakai layout aplikasi (sidebar tetap) dengan bahasa visual 403/404/500:
  warna `#001e2e`, `#005981`, `#ffae01`, IBM Plex Sans.
- UI:
  - tanpa background grid,
  - mode gelap/terang mengikuti tombol global Velzon (`html[data-bs-theme]`),
  - pesan user rata kanan, jawaban AI rata kiri,
  - area chat yang scroll hanya di dalam panel, composer tetap di bawah,
  - aksi jawaban: salin, ulangi, rating, bagikan; input suara di composer.
- Widget AI lama tetap tersedia untuk pertanyaan cepat dan memiliki tautan ke halaman penuh.

### Konteks & visual
- System prompt menegaskan: jawab hanya pertanyaan terbaru, jangan mengulang jawaban lama.
- Riwayat hanya dikirim jika pengguna memakai rujukan/kelanjutan (`itu`, `tadi`, `lanjutkan`, `revisi`, dan sejenisnya). Pertanyaan topik baru tidak membawa jawaban lama agar model tidak mengulang topik sebelumnya.
- Jika pengguna meminta grafik/tren, sistem memastikan blok chart dibuat.
- Jika pengguna meminta tabel nilai, sistem memastikan blok table dibuat.
- Fallback otomatis mengambil `attendance_trend`, `grades_summary`, `count_students`, atau `count_gtk` bila model lupa memformat visual.

### Jawaban terstruktur
AI hanya boleh mengembalikan JSON:

```json
{
  "blocks": [
    {"type": "text", "text": "..."},
    {"type": "metric", "label": "Total Santri", "value": 123, "unit": "santri"},
    {"type": "table", "title": "...", "columns": ["..."], "rows": [["..."]]},
    {"type": "chart", "title": "...", "chart_type": "bar", "labels": ["..."], "series": [{"name": "...", "data": [1,2]}]},
    {"type": "source", "label": "...", "detail": "...", "tool": "attendance_trend"}
  ]
}
```

Frontend merender blok dengan tabel aplikasi dan ApexCharts. Tidak ada HTML/JS bebas dari AI.

### Tool data read-only
Tool yang tersedia:
- `count_students`, `count_gtk`, `count_rombel`
- `attendance_summary`, `attendance_trend`
- `my_schedule_today`, `class_schedule`, `school_schedule_today`
- `grades_summary`
- `academic_overview`
- `tahfidz_summary`, `ljk_summary`
- `list_master_data`
- `search_students`, `student_detail`
- `search_gtk`, `gtk_detail`
- `count_my_students`, `list_my_classes`

Tool web:
- `web_search` — mencari berita/informasi terbaru.
- `read_url` — membaca isi halaman dari URL hasil pencarian.

Provider pencarian: Tavily/Serper/Brave bila key tersedia; tanpa key memakai DuckDuckGo lalu Wikipedia. Hasil pencarian diberi cache singkat.

Prinsip:
- Tidak ada SQL bebas dari AI.
- Selalu dibatasi `school_id`/konteks sekolah.
- Non-Pimpinan dibatasi kelas yang diampu untuk tren presensi dan nilai.
- Jadwal kelas boleh dilihat lintas kelas dalam sekolah yang sama; jadwal pribadi memakai `teacher_id` pengguna.
- NIK/KK/payroll/rekening tidak pernah dikembalikan.
- Setiap jawaban menyertakan blok `source`.

### Streaming, token, biaya
- SSE event: `status`, `delta`, `blocks`, `done`, `error`.
- Batas output data: `flash` 2.048 token, `pro` 4.096 token. Mode konten: 8.192/16.384 token.
- Hanya tool yang relevan yang dikirim (mis. pertanyaan jadwal hanya membawa tool jadwal + web). Riwayat dibatasi 8 pesan/6.000 karakter.
- Reasoning DeepSeek dimatikan untuk pertanyaan data; OpenRouter memakai effort `minimal`. Mode konten menggunakan reasoning lebih tinggi.
- Hasil jadwal dibatasi 30 baris, hasil baca halaman web 4.000 karakter.
- Jawaban yang terpotong (`finish_reason=length`) diulang sekali dengan batas lebih besar.
- Mode pembuatan konten memakai temperature lebih tinggi (0,65) agar RPM/materi tidak kaku; pertanyaan data tetap 0,2.
- Token dan biaya dicatat pada:
  - `ai_chat_messages.metadata` (per jawaban),
  - `ai_usage_logs` (per request: prompt/completion/total token, biaya, model, tools).
- Harga dapat diatur di `config/ai.php` atau ENV.

## File Utama
- `app/Http/Controllers/AiWorkspaceController.php`
- `app/Http/Controllers/AiChatController.php`
- `app/Services/Ai/AiContextService.php`
- `app/Services/Ai/AiChatService.php`
- `app/Services/Ai/AiProviderService.php`
- `app/Services/Ai/AiStructuredResponse.php`
- `app/Services/Ai/AiDataToolService.php`
- `app/Models/AiUsageLog.php`
- `resources/views/ai/index.blade.php`
- `config/ai.php`
- `database/migrations/2026_10_19_140000_create_ai_usage_logs_table.php`

## Deployment
1. Jalankan migrasi:
```bash
php artisan migrate
```
2. Isi API key di Super Admin → Pengaturan AI (DeepSeek), aktifkan dan jadikan default.
   Alternatif ENV:
```env
DEEPSEEK_API_KEY=...
DEEPSEEK_FLASH_MODEL=deepseek-flash
DEEPSEEK_PRO_MODEL=deepseek-v4-pro
```
3. Bersihkan cache:
```bash
php artisan config:clear
php artisan view:clear
php artisan route:clear
```
4. Buka `/{userId}/ai`.

## Verifikasi
```text
AiStructuredResponseTest: 7 deprecated (26 assertions)
AiChatTest: 16 deprecated (68 assertions)
```
Mencakup parser blok, sanitasi HTML, fallback aman, ketersediaan tool presensi/nilai/tahfidz/LJK, provider DeepSeek dari ENV, tabel log token/biaya, serta regresi widget/chat/workspace yang sudah ada.

## MVP Berikutnya
- Data UKS/kesehatan (sesuai izin), finance, sarpras, asrama.
- Grafik interaktif filter lanjutan.
- Ekspor tabel/grafik ke PDF/Excel.
- Halaman monitoring token/biaya untuk Super Admin.
