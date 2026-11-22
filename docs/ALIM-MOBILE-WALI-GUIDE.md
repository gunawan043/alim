# ALIM Mobile Wali — Implementation & API Handoff

> Dokumen sumber materi untuk pengembangan `alim-mobile` (React Native / Expo).
>
> **Scope:** aplikasi mobile **khusus wali santri**.
>
> **Bukan scope mobile:** GTK, LJK scanner, BUMP, SIPLah, hutang lembaga, pembayaran, dan modul keuangan GTK.

---

## 1. Tujuan aplikasi

Memberi wali santri akses aman ke informasi peserta didik:

- profil anak
- absensi
- nilai dan rapor
- tahfidz
- prestasi dan lomba
- pelanggaran
- asrama
- kesehatan
- perpustakaan
- agenda dan peraturan
- pengumuman
- izin, kunjungan, dan kepulangan
- komunikasi dengan sekolah

Aplikasi tidak boleh menampilkan data anak yang tidak terhubung dengan akun wali.

---

## 2. Prinsip keamanan

1. Mobile hanya memakai Bearer token Sanctum.
2. Mobile tidak boleh memegang token BUMP/POS.
3. Setiap request data anak wajib melewati:
   - autentikasi token
   - middleware `wali.mobile`
   - `X-Active-School-Id`
   - pengecekan relasi `wali_santri` aktif
4. Token memiliki ability per domain.
5. Data kesehatan dan rapor adalah data sensitif.
6. Jangan menampilkan NIK/KK lengkap di notifikasi atau halaman publik.
7. Aplikasi harus logout dan menghapus token saat menerima `401`.
8. Saat menerima `403 INSUFFICIENT_ABILITY` dengan `reauth_required: true`, lakukan login ulang.

---

## 3. Base URL dan header

### Base URL

```text
https://<alim-host>/api/mobile/v1
```

Contoh:

```text
https://alim.sekolah.sch.id/api/mobile/v1
```

### Header wajib

```http
Authorization: Bearer {sanctum_token}
Accept: application/json
Content-Type: application/json
```

### Header multi-sekolah

Untuk wali yang memiliki anak di lebih dari satu sekolah:

```http
X-Active-School-Id: {school_id}
```

Header ini divalidasi terhadap relasi `wali_santri` aktif. Jika header salah,
backend **tidak fallback** ke sekolah lain.

### Header opsional

```http
X-App-Version: 1.0.0
X-Request-ID: {uuid}
X-Device-Id: {installation_id}
```

---

## 4. Alur pertama kali membuka aplikasi

```text
Splash
  → cek token di SecureStore
  → jika tidak ada token: Login
  → jika ada token: GET /auth/me
  → jika 401: hapus token, ke Login
  → jika sukses:
       - ambil daftar students
       - ambil daftar schools
       - jika requires_school_selection = true: tampilkan pemilih sekolah
       - simpan school_id
       - pilih anak
       - Dashboard
```

### Endpoint bootstrap

```http
GET /auth/me
```

Response penting:

```json
{
  "success": true,
  "data": {
    "user": {
      "id": "...",
      "name": "...",
      "role": "wali",
      "is_wali": true,
      "capabilities": []
    },
    "students": [
      {
        "id": "...",
        "name": "...",
        "nik": "...",
        "role": "ayah",
        "is_primary": true,
        "school": "Nama Sekolah"
      }
    ],
    "schools": [
      {
        "id": "...",
        "name": "Sekolah A",
        "school_code": "...",
        "is_active": true,
        "students_count": 2
      }
    ],
    "active_school_id": null,
    "requires_school_selection": true
  }
}
```

---

## 5. Autentikasi

### Login

```http
POST /auth/login
```

Body:

```json
{
  "email": "wali@example.com",
  "password": "password"
}
```

Response sukses:

```json
{
  "success": true,
  "message": "Login berhasil.",
  "data": {
    "user": {},
    "access_token": "...",
    "token_type": "Bearer",
    "expires_in": 2592000,
    "expires_at": "2026-12-21T10:00:00+00:00",
    "abilities": [
      "child.read",
      "attendance.read",
      "grades.read",
      "raport.read"
    ]
  }
}
```

Jika akun bukan wali:

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validasi gagal."
  },
  "errors": {
    "email": ["Aplikasi mobile ini hanya untuk akun wali santri."]
  }
}
```

### Register

```http
POST /auth/register
```

Field utama:

```text
name
email
password
password_confirmation
no_kk
nik_wali
no_hp
hubungan
```

### 2FA

```http
POST /auth/two-factor/verify
POST /auth/two-factor/resend
```

### Google

```http
POST /auth/google
```

### Logout

```http
POST /auth/logout
POST /auth/logout-all
```

### Session management

```http
GET    /auth/sessions
PATCH  /auth/sessions/current
DELETE /auth/sessions/others
```

### Profil

```http
GET /auth/me
PUT /auth/me
```

---

## 6. Pemilihan sekolah

```http
GET /{userId}/wali/schools
```

Response:

```json
{
  "success": true,
  "data": {
    "schools": [
      {
        "id": "...",
        "name": "Sekolah A",
        "school_code": "...",
        "is_active": true,
        "students_count": 2
      }
    ],
    "active_school_id": null,
    "requires_selection": true
  }
}
```

Setelah memilih sekolah, semua request data anak mengirim:

```http
X-Active-School-Id: {school_id}
```

Jika `requires_selection = true`, aplikasi **wajib** menampilkan pemilih sekolah
sebelum membuka dashboard atau data anak.

---

## 7. Ability token

Endpoint wali memakai ability berikut:

```text
child.read
child.write
school.read
wali.link.read
wali.link.write
attendance.read
grades.read
raport.read
tahfidz.read
violation.read
achievement.read
class.read
schedule.read
calendar.read
academic.read
dormitory.read
dormitory.write
health.read
permit.read
permit.create
notification.read
notification.write
announcement.read
survey.read
survey.write
complaint.read
complaint.write
report.read
document.read
library.read
extracurricular.read
regulation.read
mahrom.read
mahrom.write
profile.read
profile.write
```

Jika ability tidak ada:

```json
{
  "success": false,
  "error": {
    "code": "INSUFFICIENT_ABILITY",
    "message": "Token tidak memiliki izin untuk aksi ini.",
    "ability": "grades.read",
    "reauth_required": true
  }
}
```

---

## 8. Format response

### Sukses

```json
{
  "success": true,
  "message": "OK",
  "data": {},
  "meta": {
    "request_id": "...",
    "timestamp": "2026-11-21T10:00:00+00:00"
  }
}
```

### Error

```json
{
  "success": false,
  "request_id": "...",
  "error": {
    "code": "NOT_FOUND",
    "message": "Resource tidak ditemukan.",
    "type": "ModelNotFoundException"
  }
}
```

### Kode error umum

```text
UNAUTHENTICATED
FORBIDDEN
NOT_FOUND
VALIDATION_ERROR
INSUFFICIENT_ABILITY
WALI_ONLY
CONFLICT
TOO_MANY_REQUESTS
SERVER_ERROR
```

### Aturan tanggal dan uang

- Tanggal: `YYYY-MM-DD`
- Waktu: ISO-8601
- Uang: angka numerik, format tampilan dilakukan di mobile
- Status: gunakan `status` dan `status_label` bila tersedia

---

## 9. Daftar endpoint

Base prefix:

```text
/api/mobile/v1
```

### 9.1 Auth

```text
POST   /auth/register
POST   /auth/login
POST   /auth/two-factor/verify
POST   /auth/two-factor/resend
POST   /auth/google
POST   /auth/logout
POST   /auth/logout-all
POST   /auth/forgot-password
POST   /auth/reset-password
GET    /auth/me
PUT    /auth/me
GET    /auth/sessions
PATCH  /auth/sessions/current
DELETE /auth/sessions/others
```

### 9.2 Penghubungan wali

Flow yang disarankan:

```text
POST /{userId}/wali/links/preview
POST /{userId}/wali/links/confirm
GET  /{userId}/wali/links/children
GET  /{userId}/wali/links/siblings/{student}
POST /{userId}/wali/links/siblings/preview
```

Legacy compatibility:

```text
POST /wali-santri/start-registration
POST /wali-santri/link
POST /wali-santri/request
GET  /wali-santri/requests
PUT  /wali-santri/requests/{tokenOrId}
DELETE /wali-santri/{id}
```

Gunakan flow `links/*` untuk implementasi baru.

### 9.3 Anak dan profil

```text
GET  /santri
GET  /santri/verify-nik
GET  /santri/{id}
POST /santri
```

### 9.4 Dashboard

```text
GET /dashboard
GET /dashboard/attendance
```

### 9.5 Pendidikan

```text
GET /santri/{id}/attendance
GET /santri/{id}/attendance/history
GET /santri/{id}/grades
GET /santri/{id}/classes
GET /santri/{id}/class-history
GET /santri/{id}/exam-schedules
GET /raport/status
GET /raport/{student_id}
GET /jadwal
GET /jadwal/week
GET /kalender
GET /academic/summary
GET /academic/rewards
```

### 9.6 Tahfidz

```text
GET /santri/{id}/tahfidz
GET /tahfidz
GET /tahfidz/progress
```

Sertifikat:

```text
GET /{userId}/wali/tahfidz/certificates/{student}
GET /{userId}/wali/tahfidz/certificates/{certificate}/pdf
```

### 9.7 Prestasi, lomba, ekstrakurikuler

```text
GET /santri/{id}/achievements
GET /santri/{id}/competitions
GET /santri/{id}/extracurricular
```

Sertifikat prestasi:

```text
GET /{userId}/wali/achievements/{id}/certificate
```

### 9.8 Pelanggaran

```text
GET /santri/{id}/violations
GET /santri/{id}/dormitory-violations
```

### 9.9 Kesehatan

```text
GET /santri/{id}/health
GET /santri/{id}/health/history
```

`health` mengembalikan profil kesehatan terkini.

`health/history` mengembalikan:

- pemeriksaan kesehatan
- pertumbuhan tinggi/berat/BMI
- izin sakit
- kunjungan/rawat UKS
- pemberian obat

### 9.10 Perpustakaan

```text
GET /santri/{id}/library/loans
GET /santri/{id}/library/visits
GET /santri/{id}/library/member-card
GET /library/books?search=
GET /library/programs
```

### 9.11 Asrama

```text
GET /santri/{id}/dormitory
GET /santri/{id}/dormitory-attendance
GET /santri/{id}/dormitory-violations
GET /santri/{id}/dormitory/history
GET /santri/{id}/dormitory/rewards
GET /dormitory-permits
POST /dormitory/permit
GET /dormitory/visits
POST /dormitory/visit
GET /dormitory/visits/{id}
PATCH /dormitory/visits/{id}/check-in
PATCH /dormitory/visits/{id}/check-out
GET /dormitory/returns
POST /dormitory/return
```

### 9.12 Izin/sakit

```text
POST /permit/request
GET  /permit
```

### 9.13 Sekolah dan guru

```text
GET /schools/{id}/profile
GET /santri/{id}/teachers
```

### 9.14 Informasi

```text
GET /{userId}/wali/announcements
GET /{userId}/wali/announcements/{id}
POST /{userId}/wali/announcements/{id}/read
POST /{userId}/wali/announcements/{id}/ack
GET /agendas
GET /regulations
GET /prayer-times
```

### 9.15 Notifikasi

```text
GET   /notifications
PUT   /notifications/{id}/read
PATCH /notifications/mark-all-read
```

### 9.16 Laporan wali

```text
GET /{userId}/wali/reports?student_id=&month=&year=
```

Berisi ringkasan:

- absensi bulanan
- izin
- kunjungan
- tahfidz
- pelanggaran
- prestasi
- penghargaan
- rapor

### 9.17 Survei dan kritik/saran

```text
GET  /{userId}/wali/surveys
GET  /{userId}/wali/surveys/{id}
POST /{userId}/wali/surveys/{id}
GET  /{userId}/wali/complaints
POST /{userId}/wali/complaints
GET  /{userId}/wali/complaints/{id}
```

### 9.18 Dokumen

```text
GET /{userId}/wali/raport/{student}/pdf
GET /{userId}/wali/achievements/{id}/certificate
GET /{userId}/wali/tahfidz/certificates/{student}
GET /{userId}/wali/tahfidz/certificates/{certificate}/pdf
```

### 9.19 Push notification device

```text
GET    /devices
POST   /devices
DELETE /devices/{device}
```

Payload register:

```json
{
  "push_token": "...",
  "provider": "fcm",
  "platform": "android",
  "device_name": "HP Wali",
  "app_version": "1.0.0",
  "device_id": "..."
}
```

---

## 10. Contoh response data penting

### Profil anak

```json
{
  "success": true,
  "data": {
    "id": "...",
    "nik": "...",
    "name": "Ahmad",
    "gender": "L",
    "birth_date": "2012-01-01",
    "birth_place": "Mataram",
    "role": "ayah",
    "is_primary": true,
    "school": {
      "id": "...",
      "name": "Sekolah A"
    }
  }
}
```

### Riwayat kesehatan

```json
{
  "success": true,
  "data": {
    "checkups": [],
    "growth": [],
    "permits": [],
    "uks_visits": [],
    "medicines": []
  }
}
```

### Perpustakaan

```json
{
  "success": true,
  "data": {
    "records": [
      {
        "loan_number": "LN-001",
        "loan_date": "2026-11-01",
        "due_date": "2026-11-08",
        "status": "active",
        "fine_amount": 0,
        "items": [
          {
            "title": "Buku Fiqih",
            "author": "Tim Penulis",
            "status": "active"
          }
        ]
      }
    ],
    "total": 1
  }
}
```

### Profil sekolah

```json
{
  "success": true,
  "data": {
    "id": "...",
    "name": "Sekolah A",
    "npsn": "...",
    "address": "...",
    "phone": "...",
    "principal_name": "...",
    "accreditation": "A"
  }
}
```

---

## 11. Struktur aplikasi Expo yang disarankan

```text
alim-mobile/
├── app/
│   ├── (auth)/
│   │   ├── login.tsx
│   │   ├── register.tsx
│   │   └── forgot-password.tsx
│   ├── (app)/
│   │   ├── _layout.tsx
│   │   ├── select-school.tsx
│   │   ├── select-child.tsx
│   │   ├── dashboard.tsx
│   │   ├── children/
│   │   │   ├── [id].tsx
│   │   │   ├── education.tsx
│   │   │   ├── health.tsx
│   │   │   ├── library.tsx
│   │   │   ├── dormitory.tsx
│   │   │   ├── achievements.tsx
│   │   │   └── extracurricular.tsx
│   │   ├── information/
│   │   │   ├── announcements.tsx
│   │   │   ├── agenda.tsx
│   │   │   ├── regulations.tsx
│   │   │   └── calendar.tsx
│   │   ├── permits/
│   │   ├── notifications.tsx
│   │   └── profile.tsx
│   └── _layout.tsx
├── src/
│   ├── api/
│   │   ├── client.ts
│   │   ├── auth.ts
│   │   ├── children.ts
│   │   ├── health.ts
│   │   ├── library.ts
│   │   ├── dormitory.ts
│   │   └── information.ts
│   ├── stores/
│   │   ├── auth.store.ts
│   │   └── school.store.ts
│   ├── components/
│   ├── hooks/
│   ├── types/
│   └── utils/
└── app.json
```

### Library yang disarankan

- `expo-router`
- `expo-secure-store`
- `expo-notifications`
- `expo-image-picker` hanya jika dibutuhkan
- `@tanstack/react-query`
- `axios` atau `fetch`
- `zod` untuk validasi payload
- `zustand` untuk state sesi/sekolah
- `date-fns` dengan locale Indonesia

---

## 12. Contoh API client TypeScript

```ts
import axios from "axios";
import * as SecureStore from "expo-secure-store";

export const api = axios.create({
  baseURL: process.env.EXPO_PUBLIC_ALIM_API_URL,
  headers: {
    Accept: "application/json",
    "Content-Type": "application/json",
  },
  timeout: 20000,
});

api.interceptors.request.use(async (config) => {
  const token = await SecureStore.getItemAsync("alim_access_token");
  const schoolId = await SecureStore.getItemAsync("alim_active_school_id");

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  if (schoolId) {
    config.headers["X-Active-School-Id"] = schoolId;
  }

  config.headers["X-App-Version"] = "1.0.0";

  return config;
});

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const status = error.response?.status;
    const code = error.response?.data?.error?.code;

    if (status === 401) {
      await SecureStore.deleteItemAsync("alim_access_token");
      // arahkan ke halaman login
    }

    if (status === 403 && code === "INSUFFICIENT_ABILITY") {
      // logout dan login ulang
    }

    return Promise.reject(error);
  }
);
```

---

## 13. State sesi yang perlu disimpan

```text
access_token
expires_at
user
active_school_id
selected_student_id
last_sync_at
```

Gunakan `expo-secure-store`, bukan AsyncStorage biasa, untuk token.

---

## 14. Screen MVP

### Auth
- Login
- Register
- Lupa password
- 2FA

### Onboarding
- Pilih sekolah
- Pilih anak
- Penghubungan anak

### Dashboard
- Ringkasan absensi
- Nilai terbaru
- Tahfidz
- Pengumuman
- Pelanggaran/prestasi terbaru

### Anak
- Profil
- Absensi
- Nilai
- Rapor
- Kelas
- Jadwal
- Tahfidz
- Prestasi/lomba
- Pelanggaran
- Kesehatan
- Perpustakaan
- Asrama
- Ekstrakurikuler
- Guru

### Informasi
- Pengumuman
- Notifikasi
- Agenda
- Peraturan
- Kalender
- Jadwal shalat
- Jadwal ujian

### Pengajuan
- Izin/sakit
- Kunjungan
- Kepulangan
- Survei
- Kritik/saran

---

## 15. Aturan UX penting

1. Jika wali memiliki lebih dari satu anak, tampilkan child switcher.
2. Jika lebih dari satu sekolah, tampilkan school switcher.
3. Jangan meminta wali memasukkan `school_id` secara manual.
4. Tampilkan `requires_school_selection` sebagai langkah onboarding.
5. Tampilkan status data terakhir diperbarui.
6. Tampilkan empty state yang jelas, bukan error.
7. Jangan tampilkan data kesehatan di notifikasi push.
8. Jangan tampilkan NIK/KK penuh di kartu ringkasan.
9. Saat token expired, jangan menampilkan pesan teknis; arahkan login ulang.
10. Saat `403 INSUFFICIENT_ABILITY`, lakukan re-login otomatis atau tampilkan tombol login ulang.

---

## 16. Push notification

Gunakan `expo-notifications.getDevicePushTokenAsync()`.

- Android → FCM
- iOS → APNs

Daftarkan setelah login:

```http
POST /devices
```

Saat logout:

```http
DELETE /devices/{device}
```

Event yang disarankan:

```text
absensi tidak hadir
pelanggaran baru
prestasi baru
izin disetujui/ditolak
pengumuman baru
rapor terbit
tahfidz terbaru
kunjungan disetujui
```

---

## 17. File dan dokumen

Gunakan endpoint dokumen yang membutuhkan token. Jangan menyimpan URL storage
mentah di aplikasi.

- rapor PDF
- sertifikat prestasi
- sertifikat tahfidz
- dokumen peraturan
- foto kegiatan

---

## 18. Offline/cache

Untuk MVP, aplikasi boleh online-first.

Jika ingin offline:

- cache profil anak
- cache absensi terakhir
- cache pengumuman
- cache jadwal
- cache kalender
- tampilkan `last_sync_at`
- retry request dengan backoff
- gunakan idempotency untuk pengajuan izin/kunjungan

Jangan cache data kesehatan secara permanen di perangkat.

---

## 19. Checklist implementasi mobile

### Fondasi
- [ ] Setup Expo + TypeScript
- [ ] Environment API URL
- [ ] SecureStore token
- [ ] API client
- [ ] Error interceptor 401/403
- [ ] Auth store
- [ ] School store

### Auth
- [ ] Login
- [ ] Register
- [ ] Lupa password
- [ ] 2FA
- [ ] Logout
- [ ] Session list
- [ ] Login ulang saat ability berubah

### Data anak
- [ ] Multi-anak
- [ ] Multi-sekolah
- [ ] Profil
- [ ] Absensi
- [ ] Nilai
- [ ] Rapor
- [ ] Tahfidz
- [ ] Prestasi
- [ ] Pelanggaran
- [ ] Kesehatan
- [ ] Perpustakaan
- [ ] Asrama
- [ ] Ekstrakurikuler
- [ ] Guru
- [ ] Ujian

### Informasi
- [ ] Pengumuman
- [ ] Notifikasi
- [ ] Agenda
- [ ] Peraturan
- [ ] Kalender
- [ ] Jadwal shalat

### Pengajuan
- [ ] Izin/sakit
- [ ] Kunjungan
- [ ] Kepulangan
- [ ] Survei
- [ ] Kritik/saran

### Produksi
- [ ] Push notification
- [ ] Deep link
- [ ] Signed document URL
- [ ] Offline/cache
- [ ] Error analytics
- [ ] Device testing Android/iOS
- [ ] Build EAS
- [ ] Play Store/App Store

---

## 20. Checklist backend sebelum release

- [ ] Seluruh test mobile lulus
- [ ] Ability enforcement aktif
- [ ] Multi-school teruji
- [ ] Cross-tenant test lulus
- [ ] Health data access audit
- [ ] Signed URL dokumen
- [ ] Push notification event
- [ ] Rate limit login
- [ ] Token cleanup scheduler
- [ ] Monitoring error API
- [ ] Backup database

---

## 21. Yang tidak boleh dibawa ke mobile

```text
GTK login
LJK scanner
BUMP ordering
SIPLah proposal
Hutang lembaga
Pembayaran
Keuangan
```

Mobile hanya untuk wali santri.

---

## 22. Dokumentasi pendamping

- `WALI_SANTRI_API_SCHEMA.md`
- `MOBILE-API-DESIGN.md` (desain awal; sebagian sudah kedaluwarsa)
- `docs/features/LJK-SMART-SCANNER.md` (LJK hanya web GTK)

---

## 23. Definition of Done MVP

MVP dianggap selesai jika wali dapat:

1. Login dan memilih sekolah.
2. Melihat daftar anak.
3. Melihat dashboard anak.
4. Melihat absensi, nilai, dan rapor.
5. Melihat tahfidz.
6. Melihat prestasi dan pelanggaran.
7. Melihat data asrama.
8. Melihat kesehatan dasar dan riwayat.
9. Melihat perpustakaan.
10. Melihat pengumuman dan notifikasi.
11. Melihat agenda dan kalender.
12. Mengajukan izin/kunjungan.
13. Menerima push notification.
14. Logout dengan aman.

Jika 14 poin di atas terpenuhi, aplikasi sudah layak masuk uji coba terbatas.
