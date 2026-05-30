# ALIM Mobile API — Desain Arsitektur Backend & Mobile

> **Latar belakang:** ALIM adalah aplikasi Laravel (PHP) existing. Opsi A dipilih — extend Laravel dengan API route + controller/service baru, reuse existing User model.

---

## 1. ANALISIS ERD — Many-to-Many Wali ↔ Santri

```
┌──────────────────────────────────────────────────────────────┐
│                      users (existing table)                  │
│                                                              │
│  id:uuid PK                                                   │
│  email (unique)                                              │
│  password                                                    │
│  name                                                        │
│  is_active                                                   │
│  avatar                                                      │
│  ── NEW COLUMNS ──                                           │
│  google_id      VARCHAR(191) UNIQUE NULL                     │
│  no_kk          VARCHAR(30)   NULL  ← Kartu Keluarga wali    │
│  nik_wali       VARCHAR(30)   NULL  ← NIK wali sendiri       │
│  no_hp          VARCHAR(20)   NULL                           │
│  hubungan       ENUM(...)     NULL  ← ayah|ibu|kakek|dll   │
│  is_wali        BOOLEAN       NOT NULL DEFAULT FALSE          │
│  google_token   TEXT          NULL                           │
└──────────────────────────────────────────────────────────────┘
           │                                                      │
           │                        ┌──────────────────────────────────┐
           │                        │         wali_santri (PIVOT)       │
           │                        │                                  │
           │                        │  user_id        UUID FK→users     │
           │                        │  student_id     UUID FK→students  │
           │                        │  role           VARCHAR(20)       │
           │                        │  is_primary     BOOLEAN           │
           │                        │  status         ENUM              │
           │                        │  verified_at    TIMESTAMP NULL     │
           │                        │  verified_by   UUID FK→users NULL │
           │                        │  created_at / updated_at         │
           │                        │  UNIQUE(user_id, student_id, role)│
           └────────────────────────│  CHECK(user_id != student_id)     │
                                     └──────────────────────────────────┘
                                                    ▲
                                                    │
                           ┌──────────────────────────────────────────┐
                           │           students (existing table)       │
                           │                                          │
                           │  id:uuid PK                               │
                           │  nik         VARCHAR(30) UNIQUE           │
                           │  no_kk       VARCHAR(30)                  │
                           │  name, gender, birth_date, birth_place     │
                           │  school_id   FK→schools                   │
                           │  user_id     FK→users (NULL — skip!)      │
                           │  other fields (address, parent info…)     │
                           │  status      ENUM('active','inactive'…)  │
                           └──────────────────────────────────────────┘
```

### Skema M:N : 1 Wali → 3 Santri, 1 Santri → 2 Wali

```
Skenario:
┌──────────────────────────────────────────────────────────────────┐
│  WALI (User: Budi Santoso — ayah)                                │
│    ├─ WaliSantri(id:uuid, user:Budi, student:Anak1, role:ayah, is_primary:true) │
│    ├─ WaliSantri(id:uuid, user:Budi, student:Anak2, role:ayah, is_primary:true) │
│    └─ WaliSantri(id:uuid, user:Budi, student:Anak3, role:ayah, is_primary:true) │
│                                                                  │
│  WALI (User: Siti Santoso — ibu)                                 │
│    ├─ WaliSantri(id:uuid, user:Siti, student:Anak1, role:ibu, is_primary:false) │
│    └─ WaliSantri(id:uuid, user:Siti, student:Anak2, role:ibu, is_primary:false) │
│                                                                  │
│  WALI (User: Kakek — kakek)                                      │
│    └─ WaliSantri(id:uuid, user:Kakek, student:Anak1, role:kakek, is_primary:false) │
└──────────────────────────────────────────────────────────────────┘

 Anak1 → 3 wali (ayah, ibu, kakek)
 Anak2 → 2 wali (ayah, ibu)
 Anak3 → 1 wali  (ayah)
 Budi  → 3 anak (Anak1, Anak2, Anak3)
 Siti  → 2 anak (Anak1, Anak2)
```

### Mengapa M:N via pivot (bukan `students.user_id`)?

| Pendekatan | Problem |
|---|---|
| `students.user_id` (1:N) | 1 Santi hanya punya 1 wali. Tidak bisa punya ayah + ibu. ❌ |
| `users.no_kk` → lookup students | Rumit, tidak bisa model peran (ayah vs kakek). ❌ |
| **wali_santri pivot** | ✅ Peran fleksibel (ayah, ibu, kakek…). ✅ Satu Santi banyak wali. ✅ Satu wali banyak Santi. |

---

## 2. DESAIN TABEL BARU

### 2.1 Extend `users` — Kolom Baru

```sql
-- Tambahkan ke tabel users yang sudah ada
ALTER TABLE users ADD COLUMN google_id    VARCHAR(191)  UNIQUE NULL;
ALTER TABLE users ADD COLUMN no_kk        VARCHAR(30)   NULL;
ALTER TABLE users ADD COLUMN nik_wali     VARCHAR(30)   NULL;
ALTER TABLE users ADD COLUMN no_hp        VARCHAR(20)   NULL;
ALTER TABLE users ADD COLUMN hubungan     ENUM('ayah','ibu','kakek','nenek','wali','lainnya') NULL;
ALTER TABLE users ADD COLUMN is_wali      BOOLEAN      NOT NULL DEFAULT FALSE;
ALTER TABLE users ADD COLUMN google_token TEXT         NULL;

ALTER TABLE users ADD INDEX idx_users_is_wali (is_wali);
ALTER TABLE users ADD INDEX idx_users_no_kk (no_kk);
```

### 2.2 Tabel: `wali_santri` (PIVOT M:N)

```sql
CREATE TABLE wali_santri (
    id              UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id         UUID        NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    student_id      UUID        NOT NULL REFERENCES students(id) ON DELETE CASCADE,
    role            VARCHAR(20) NOT NULL DEFAULT 'wali',
    is_primary      BOOLEAN     NOT NULL DEFAULT FALSE,
    status          VARCHAR(20) NOT NULL DEFAULT 'pending',
    verified_at     TIMESTAMP   NULL,
    verified_by     UUID        NULL REFERENCES users(id),
    created_at      TIMESTAMP   NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP   NOT NULL DEFAULT NOW(),

    UNIQUE (user_id, student_id, role),
    CHECK (user_id != student_id)
);

CREATE INDEX idx_wali_santri_user    ON wali_santri(user_id);
CREATE INDEX idx_wali_santri_student ON wali_santri(student_id);
CREATE INDEX idx_wali_santri_status  ON wali_santri(status);
CREATE INDEX idx_wali_santri_primary ON wali_santri(student_id) WHERE is_primary = TRUE;
```

### 2.3 Tabel: `wali_registration_tokens` (Verifikasi Otorisasi)

```sql
CREATE TABLE wali_registration_tokens (
    id              UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
    token           VARCHAR(64) UNIQUE NOT NULL,
    user_id         UUID        NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    student_id      UUID        NULL REFERENCES students(id) ON DELETE CASCADE,
    nik_santri      VARCHAR(30) NOT NULL,
    no_kk           VARCHAR(30) NULL,
    intent          VARCHAR(30) NOT NULL,  -- 'link_student' | 'add_wali' | 'register_first'
    expires_at      TIMESTAMP   NOT NULL,
    used_at         TIMESTAMP   NULL,
    created_at      TIMESTAMP   NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_wali_reg_token ON wali_registration_tokens(token);
CREATE INDEX idx_wali_reg_user  ON wali_registration_tokens(user_id);
```

---

## 3. STRATEGI VALIDASI No KK & NIK

### 3.1 Validasi Format NIK (Tier 1 — selalu dilakukan)

```php
// php: App\Services\WaliSantriService.php

public function validateNikFormat(string $nik): array
{
    $errors = [];

    // 1. Panjang harus 16 digit
    if (!preg_match('/^\d{16}$/', $nik)) {
        return ['valid' => false, 'errors' => ['NIK harus terdiri dari tepat 16 digit angka.']];
    }

    // 2. Digit kode provinsi (2 digit pertama)
    $provinceCode = (int) substr($nik, 0, 2);
    if ($provinceCode < 1 || $provinceCode > 91) {
        $errors[] = 'Kode provinsi pada NIK tidak valid.';
    }

    // 3. Digit kode gender (digit ke-7): ganjil=perempuan, genap=laki-laki
    $genderDigit = (int) $nik[6];
    if ($genderDigit === 0) {
        $errors[] = 'Digit kode gender pada NIK tidak valid.';
    }

    return ['valid' => count($errors) === 0, 'errors' => $errors];
}
```

### 3.2 Strategi Anti-Duplikasi (Tier 2)

```
CEK NIK SAAT REGISTRASI SANTRI:
─────────────────────────────────────────────────────────────────
1. CHECK students.nik == input_nik ?
   └─ IF NOT EXISTS:
       → Lanjut registrasi (NIK baru, belum terdaftar)
   └─ IF EXISTS:
       2. CHECK wali_santri WHERE student_id = siswa AND user_id = current_user AND status='active' ?
          └─ IF EXISTS: return "Sudah terhubung dengan akun Anda" (OK)
          └─ IF NOT EXISTS:
              → NIK sudah terdaftar di orang lain.
              → ERROR: "NIK sudah terdaftar di sistem dan milik orang lain.
                        Gunakan menu 'Minta Jadi Wali' untuk mengajukan hubungan."
─────────────────────────────────────────────────────────────────
```

### 3.3 Validasi No KK

```php
// Format: tepat 16 digit angka
// Validasi regex: /^\d{16}$/

// KK wajib saat:
// - Registrasi wali pertama kali: opsional (bisa kosong dulu)
// - Daftarkan Santi pertama: WAJIB (biar bisa link)
// - Minta jadi wali kedua: WAJIB (untuk validasi match)
```

---

## 4. ENDPOINT API MINIMAL

### 4.1 Auth
```
POST /api/mobile/v1/auth/register          Registrasi wali (email/password)
POST /api/mobile/v1/auth/login             Login wali
POST /api/mobile/v1/auth/google           Login/Registrasi Google OAuth
POST /api/mobile/v1/auth/logout            Logout (invalidate JWT)
GET  /api/mobile/v1/auth/me                Get current user profile
PUT  /api/mobile/v1/auth/me                Update profile (no_kk, nik_wali, no_hp)
POST /api/mobile/v1/auth/password/email   Kirim reset password link
POST /api/mobile/v1/auth/password/reset    Reset password dengan token
```

### 4.2 Santri
```
GET  /api/mobile/v1/santri                 List semua Santi (dashboard wali)
GET  /api/mobile/v1/santri/:id             Detail satu Santi (with other walis)
POST /api/mobile/v1/santri                 Daftarkan Santi baru + klaim ke wali
POST /api/mobile/v1/santri/verify-nik      Cek apakah NIK sudah terdaftar
```

### 4.3 Wali-Santri Connection
```
POST /api/mobile/v1/wali-santri/link       Klaim Santi yang sudah ada ke akun wali
POST /api/mobile/v1/wali-santri/request    Minta jadi wali kedua/ketiga
GET  /api/mobile/v1/wali-santri/requests   List request approval (untuk wali utama)
PUT  /api/mobile/v1/wali-santri/requests/:token  Approve/Reject request
DELETE /api/mobile/v1/wali-santri/:id      Lepas hubungan wali-Santi
```

### 4.4 Dashboard
```
GET /api/mobile/v1/dashboard                 Ringkasan: jumlah Santi, statistik
GET /api/mobile/v1/dashboard/attendance     Absensi semua Santi tanggal tertentu
```

---

## 5. CONTOH REQUEST-RESPONSE KRITIS

### 5.1 Endpoint: Daftarkan Santi + Klaim ke Wali (Ayah)

**Request:**
```http
POST /api/mobile/v1/santri
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
Content-Type: application/json

{
  "nik": "3201234567890001",
  "name": "Ahmad Fauzan Santoso",
  "gender": "L",
  "birth_place": "Bandung",
  "birth_date": "2015-03-15",
  "no_kk": "3201123456789000",
  "role": "ayah"
}
```

**Response BERHASIL (201):**
```json
{
  "success": true,
  "message": "Santri berhasil terdaftar dan terhubung dengan akun wali.",
  "data": {
    "student": {
      "id": "550e8400-e29b-41d4-a716-446655440001",
      "nik": "3201234567890001",
      "name": "Ahmad Fauzan Santoso",
      "gender": "L",
      "birth_date": "2015-03-15",
      "status": "active"
    },
    "wali_santri": {
      "id": "660e8400-e29b-41d4-a716-446655440002",
      "role": "ayah",
      "is_primary": true,
      "status": "active"
    }
  }
}
```

**Response ERROR — NIK sudah terdaftar di orang lain (409):**
```json
{
  "success": false,
  "error": {
    "code": "NIK_ALREADY_EXISTS",
    "message": "NIK 3201234567890001 sudah terdaftar di sistem dan milik orang lain.",
    "details": {
      "nik": "3201234567890001",
      "existing_wali_role": "ayah",
      "suggestion": "Gunakan menu 'Minta Jadi Wali' untuk mengajukan hubungan."
    }
  }
}
```

**Response ERROR — KK tidak cocok (422):**
```json
{
  "success": false,
  "error": {
    "code": "KK_MISMATCH",
    "message": "No KK yang Anda masukkan tidak cocok dengan data KK Santi ini."
  }
}
```

---

### 5.2 Endpoint: Tambahkan Wali Kedua (Ibu) ke Santi yang Sudah Ada

**Request:**
```http
POST /api/mobile/v1/wali-santri/request
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
Content-Type: application/json

{
  "nik_santri": "3201234567890001",
  "role": "ibu",
  "no_kk": "3201123456789000"
}
```

**Response BERHASIL — Butuh Approval Wali Utama (202):**
```json
{
  "success": true,
  "message": "Permintaan menjadi wali telah dikirim. Menunggu persetujuan dari wali utama (Ayah).",
  "data": {
    "student": {
      "id": "550e8400-e29b-41d4-a716-446655440001",
      "name": "Ahmad Fauzan Santoso"
    },
    "role": "ibu",
    "status": "pending_approval",
    "approval_required_from": {
      "name": "Budi Santoso",
      "notification_sent": true
    }
  }
}
```

**Response BERHASIL — Wali Utama (Langsung Active, 201):**
```json
{
  "success": true,
  "message": "Santri berhasil terhubung dengan akun Anda.",
  "data": {
    "student": {
      "id": "550e8400-e29b-41d4-a716-446655440001",
      "name": "Ahmad Fauzan Santoso"
    },
    "wali_santri": {
      "id": "770e8400-e29b-41d4-a716-446655440003",
      "role": "ayah",
      "is_primary": true,
      "status": "active"
    }
  }
}
```

**Response ERROR — KK tidak valid (422):**
```json
{
  "success": false,
  "error": {
    "code": "KK_MISMATCH",
    "message": "No KK yang Anda masukkan tidak cocok dengan data KK Santi ini.",
    "details": {
      "hint": "Hubungi wali utama untuk mengkonfirmasi No KK yang benar."
    }
  }
}
```

**Response ERROR — Maksimal 5 wali per Santi (409):**
```json
{
  "success": false,
  "error": {
    "code": "MAX_WALI_EXCEEDED",
    "message": "Santi ini sudah memiliki maksimal 5 wali."
  }
}
```

**Response ERROR — Request pending sudah ada (409):**
```json
{
  "success": false,
  "error": {
    "code": "DUPLICATE_REQUEST",
    "message": "Anda sudah memiliki permintaan pending untuk Santi ini."
  }
}
```

---

## 6. IMPLEMENTASI NODE.JS + EXPRESS + PRISMA (Reference)

> Folder: `alim-mobile-api/` — terpisah dari project Laravel utama.
> Ini adalah implementasi reference. Di deploy sebagai service terpisah atau bisa
> di-convert jadi Laravel API (rekomendasi akhir).
>
> **Rekomendasi akhir:** Tambahkan ke Laravel yang sudah ada (bukan service terpisah).
> Lihat Bagian 7 untuk panduan integrasi Laravel.

### Struktur Direktori

```
alim-mobile-api/
├── prisma/
│   └── schema.prisma          # Schema database (extend dari migration Laravel)
├── src/
│   ├── index.js               # Entry point
│   ├── middleware/
│   │   ├── auth.middleware.js # JWT auth middleware
│   │   └── error.middleware.js
│   ├── controllers/
│   │   ├── auth.controller.js
│   │   ├── student.controller.js
│   │   ├── wali-santri.controller.js
│   │   └── dashboard.controller.js
│   ├── services/
│   │   ├── auth.service.js
│   │   ├── student.service.js
│   │   ├── wali-santri.service.js
│   │   └── dashboard.service.js
│   ├── routes/
│   │   └── v1.routes.js
│   └── validators/
│       └── index.js           # Joi schemas
├── .env.example
└── package.json
```

### 6.1 Prisma Schema

```prisma
// alim-mobile-api/prisma/schema.prisma

datasource db {
  provider = "mysql"
  url      = env("DATABASE_URL")
}

generator client {
  provider = "prisma-client-js"
}

model User {
  id            String   @id @default(uuid())
  name          String
  email         String   @unique
  password      String?
  googleId      String?  @unique @map("google_id")
  noKk          String?  @map("no_kk")
  nikWali       String?  @map("nik_wali")
  noHp          String?  @map("no_hp")
  hubungan      String?
  isWali        Boolean  @default(false) @map("is_wali")
  isActive      Boolean  @default(true) @map("is_active")
  emailVerifiedAt DateTime? @map("email_verified_at")
  createdAt     DateTime @default(now()) @map("created_at")
  updatedAt     DateTime @updatedAt @map("updated_at")

  waliSantri    WaliSantri[]
  tokens        SecureAccessToken[]

  @@index([isWali])
  @@index([noKk])
}

model Student {
  id           String   @id @default(uuid())
  nisn         String?  @unique
  nik          String   @unique
  noKk         String?
  name         String
  gender       String
  birthPlace   String?  @map("birth_place")
  birthDate    DateTime? @map("birth_date")
  address      String?
  mobilePhone  String?  @map("mobile_phone")
  schoolId     String?  @map("school_id")
  status       String   @default("active")
  createdAt    DateTime @default(now()) @map("created_at")
  updatedAt    DateTime @updatedAt @map("updated_at")

  waliSantri   WaliSantri[]
  attendances  StudentAttendance[]

  @@index([nik])
}

model WaliSantri {
  id         String   @id @default(uuid())
  userId     String   @map("user_id")
  studentId  String   @map("student_id")
  role       String   @default("wali")
  isPrimary  Boolean  @default(false) @map("is_primary")
  status     String   @default("pending")
  verifiedAt DateTime? @map("verified_at")
  verifiedBy String?  @map("verified_by")
  createdAt  DateTime @default(now()) @map("created_at")
  updatedAt  DateTime @updatedAt @map("updated_at")

  user       User     @relation(fields: [userId], references: [id], onDelete: Cascade)
  student    Student  @relation(fields: [studentId], references: [id], onDelete: Cascade)

  @@unique([userId, studentId, role])
  @@index([userId])
  @@index([studentId])
  @@index([status])
}

model StudentAttendance {
  id            String   @id @default(uuid())
  studentId     String   @map("student_id")
  attendanceDate DateTime @map("attendance_date")
  status        String
  arrivalTime   String?  @map("arrival_time")
  notes         String?
  createdAt     DateTime @default(now()) @map("created_at")
  updatedAt     DateTime @updatedAt @map("updated_at")

  student       Student  @relation(fields: [studentId], references: [id], onDelete: Cascade)

  @@index([studentId])
  @@index([attendanceDate])
}

model SecureAccessToken {
  id         String   @id @default(uuid())
  userId     String   @map("user_id")
  token      String   @unique
  expiresAt  DateTime @map("expires_at")
  createdAt  DateTime @default(now()) @map("created_at")

  user       User     @relation(fields: [userId], references: [id], onDelete: Cascade)
}

model Notification {
  id        String   @id @default(uuid())
  userId    String   @map("user_id")
  type      String
  title     String
  message   String
  data      String?
  isRead    Boolean  @default(false) @map("is_read")
  createdAt DateTime @default(now()) @map("created_at")

  @@index([userId])
}
```

### 6.2 Middleware: JWT Auth

```javascript
// src/middleware/auth.middleware.js

const jwt = require('jsonwebtoken');
const { PrismaClient } = require('@prisma/client');

const prisma = new PrismaClient();

/**
 * Middleware: Autentikasi JWT untuk mobile API.
 * Verifikasi Bearer token dan inject user object ke request.
 */
const authenticate = async (req, res, next) => {
  const authHeader = req.headers.authorization;

  if (!authHeader || !authHeader.startsWith('Bearer ')) {
    return res.status(401).json({
      success: false,
      error: {
        code: 'UNAUTHORIZED',
        message: 'Token autentikasi diperlukan.',
      },
    });
  }

  const token = authHeader.slice(7);

  try {
    const decoded = jwt.verify(token, process.env.JWT_SECRET);

    const user = await prisma.user.findUnique({
      where: { id: decoded.sub },
      select: {
        id: true,
        name: true,
        email: true,
        isWali: true,
        isActive: true,
        noKk: true,
      },
    });

    if (!user) {
      return res.status(401).json({
        success: false,
        error: { code: 'USER_NOT_FOUND', message: 'User tidak ditemukan.' },
      });
    }

    if (!user.isActive) {
      return res.status(403).json({
        success: false,
        error: { code: 'ACCOUNT_DISABLED', message: 'Akun tidak aktif.' },
      });
    }

    req.user = user;
    next();
  } catch (err) {
    if (err.name === 'TokenExpiredError') {
      return res.status(401).json({
        success: false,
        error: { code: 'TOKEN_EXPIRED', message: 'Token sudah kadaluarsa.' },
      });
    }
    return res.status(401).json({
      success: false,
      error: { code: 'INVALID_TOKEN', message: 'Token tidak valid.' },
    });
  }
};

/**
 * Middleware: Opsional — hanya izinkan user dengan role tertentu.
 */
const requireRole = (...roles) => {
  return (req, res, next) => {
    if (!req.user || !roles.includes(req.user.role)) {
      return res.status(403).json({
        success: false,
        error: { code: 'FORBIDDEN', message: 'Anda tidak memiliki akses.' },
      });
    }
    next();
  };
};

module.exports = { authenticate, requireRole };
```

### 6.3 Services

```javascript
// src/services/wali-santri.service.js

const { PrismaClient } = require('@prisma/client');
const crypto = require('crypto');

const prisma = new PrismaClient();
const MAX_WALI_PER_STUDENT = 5;

class WaliSantriService {
  // ── Validate NIK format ──────────────────────────────────────────────────

  validateNikFormat(nik) {
    const errors = [];

    if (!/^\d{16}$/.test(nik)) {
      return { valid: false, errors: ['NIK harus terdiri dari tepat 16 digit angka.'] };
    }

    const provinceCode = parseInt(nik.slice(0, 2), 10);
    if (provinceCode < 1 || provinceCode > 91) {
      errors.push('Kode provinsi pada NIK tidak valid.');
    }

    const genderDigit = parseInt(nik[6], 10);
    if (genderDigit === 0) {
      errors.push('Digit kode gender pada NIK tidak valid.');
    }

    return { valid: errors.length === 0, errors };
  }

  // ── Check NIK availability ──────────────────────────────────────────────

  async checkNikAvailability(nik, userId) {
    const student = await prisma.student.findUnique({ where: { nik } });

    if (!student) {
      return { available: true, exists: false };
    }

    const existingLink = await prisma.waliSantri.findFirst({
      where: { studentId: student.id, userId, status: 'active' },
    });

    if (existingLink) {
      return {
        available: false,
        exists: true,
        status: 'already_linked_to_you',
        studentName: student.name,
      };
    }

    return {
      available: false,
      exists: true,
      status: 'registered_by_other',
      studentName: student.name,
      suggestion: "Gunakan menu 'Minta Jadi Wali' untuk mengajukan.",
    };
  }

  // ── Register student and link to wali ─────────────────────────────────

  async registerStudentAndLink(data, user) {
    const validation = this.validateNikFormat(data.nik);
    if (!validation.valid) {
      const err = new Error('INVALID_NIK_FORMAT');
      err.code = 422;
      err.details = { nik: data.nik, errors: validation.errors };
      throw err;
    }

    // Cek apakah NIK sudah terdaftar
    const existing = await prisma.student.findUnique({ where: { nik: data.nik } });

    if (existing) {
      // Cek apakah sudah terhubung ke wali ini
      const link = await prisma.waliSantri.findUnique({
        where: {
          userId_studentId_role: {
            userId: user.id,
            studentId: existing.id,
            role: data.role || 'wali',
          },
        },
      });

      if (link && link.status === 'active') {
        return {
          student: existing,
          waliSantri: link,
          alreadyLinked: true,
        };
      }

      // NIK terdaftar di orang lain
      const err = new Error('NIK_ALREADY_EXISTS');
      err.code = 409;
      err.details = {
        nik: data.nik,
        suggestion: "Gunakan menu 'Minta Jadi Wali' untuk mengajukan.",
      };
      throw err;
    }

    // Buat student baru dalam transaction
    return await prisma.$transaction(async (tx) => {
      const student = await tx.student.create({
        data: {
          nik: data.nik,
          name: data.name,
          gender: data.gender,
          birthPlace: data.birth_place,
          birthDate: data.birth_date ? new Date(data.birth_date) : null,
          noKk: data.no_kk,
          status: 'active',
        },
      });

      const link = await tx.waliSantri.create({
        data: {
          userId: user.id,
          studentId: student.id,
          role: data.role || 'wali',
          isPrimary: true,
          status: 'active',
          verifiedAt: new Date(),
        },
      });

      // Create notification
      await tx.notification.create({
        data: {
          userId: user.id,
          type: 'student_linked',
          title: 'Santri Baru Terhubung',
          message: `${student.name} berhasil didaftarkan dan terhubung dengan akun Anda.`,
          data: JSON.stringify({ studentId: student.id }),
        },
      });

      return { student, waliSantri: link, alreadyLinked: false };
    });
  }

  // ── Request to link to existing student (wali kedua/ketiga) ────────────

  async requestLinkToStudent(data, user) {
    const { nik_santri, role, no_kk } = data;

    // Validasi NIK format
    const validation = this.validateNikFormat(nik_santri);
    if (!validation.valid) {
      const err = new Error('INVALID_NIK_FORMAT');
      err.code = 422;
      err.details = { errors: validation.errors };
      throw err;
    }

    // Cek apakah student ada
    const student = await prisma.student.findUnique({ where: { nik: nik_santri } });
    if (!student) {
      const err = new Error('STUDENT_NOT_FOUND');
      err.code = 404;
      err.details = { nik: nik_santri };
      throw err;
    }

    // Validasi KK match (jika siswa punya no_kk)
    if (student.noKk && no_kk && student.noKk !== no_kk) {
      const err = new Error('KK_MISMATCH');
      err.code = 422;
      err.details = {
        message: 'No KK tidak cocok dengan data Santi.',
        hint: `KK Santi adalah: ${student.noKk.slice(0, 4)}••••••••`,
      };
      throw err;
    }

    // Cek apakah sudah ada link pending atau aktif
    const existingLink = await prisma.waliSantri.findUnique({
      where: {
        userId_studentId_role: {
          userId: user.id,
          studentId: student.id,
          role,
        },
      },
    });

    if (existingLink && existingLink.status === 'active') {
      return {
        student,
        waliSantri: existingLink,
        alreadyLinked: true,
        message: 'Santi sudah terhubung dengan akun Anda.',
      };
    }

    if (existingLink && existingLink.status === 'pending') {
      const err = new Error('DUPLICATE_REQUEST');
      err.code = 409;
      err.details = {
        message: 'Anda sudah memiliki permintaan pending untuk Santi ini.',
        wali_santri_id: existingLink.id,
      };
      throw err;
    }

    // Cek maksimal wali per student
    const activeWaliCount = await prisma.waliSantri.count({
      where: { studentId: student.id, status: 'active' },
    });

    if (activeWaliCount >= MAX_WALI_PER_STUDENT) {
      const err = new Error('MAX_WALI_EXCEEDED');
      err.code = 409;
      err.details = { max: MAX_WALI_PER_STUDENT };
      throw err;
    }

    // Cek apakah ada wali utama yang aktif
    const primaryLink = await prisma.waliSantri.findFirst({
      where: { studentId: student.id, isPrimary: true, status: 'active' },
      include: { user: { select: { id: true, name: true, email: true } } },
    });

    // Buat access token
    const accessToken = crypto.randomBytes(32).toString('hex');

    // Jika ada wali utama → status = pending (butuh approval)
    // Jika tidak ada → langsung active (menjadi wali utama)
    const newStatus = primaryLink && primaryLink.userId !== user.id ? 'pending' : 'active';

    const link = await prisma.$transaction(async (tx) => {
      // Hapus record pending yang lama jika ada
      if (existingLink) {
        await tx.waliSantri.delete({ where: { id: existingLink.id } });
      }

      const newLink = await tx.waliSantri.create({
        data: {
          userId: user.id,
          studentId: student.id,
          role,
          isPrimary: newStatus === 'active',
          status: newStatus,
          verifiedAt: newStatus === 'active' ? new Date() : null,
        },
      });

      // Buat registration token untuk approval
      const expiresAt = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000); // 7 hari
      await tx.secureAccessToken.create({
        data: {
          userId: primaryLink?.userId || user.id,
          token: accessToken,
          expiresAt,
        },
      });

      // Kirim notifikasi ke wali utama jika butuh approval
      if (newStatus === 'pending' && primaryLink) {
        await tx.notification.create({
          data: {
            userId: primaryLink.userId,
            type: 'wali_approval_request',
            title: 'Permintaan Menjadi Wali',
            message: `${user.name} ingin menjadi ${role} dari ${student.name}. `
              + 'Izinkan dari menu Santri.',
            data: JSON.stringify({
              studentId: student.id,
              requesterId: user.id,
              requesterName: user.name,
              role,
              accessToken,
            }),
          },
        });
      }

      return newLink;
    });

    return {
      student,
      waliSantri: link,
      alreadyLinked: false,
      needsApproval: newStatus === 'pending',
      approvalToken: newStatus === 'pending' ? accessToken : null,
      message: newStatus === 'pending'
        ? `Permintaan telah dikirim. Menunggu persetujuan dari ${primaryLink?.user?.name || 'wali utama'}.`
        : 'Santi berhasil terhubung dengan akun Anda.',
    };
  }

  // ── Approve / reject request ────────────────────────────────────────────

  async approveRejectRequest(token, user, action, note = null) {
    const accessToken = await prisma.secureAccessToken.findUnique({
      where: { token },
    });

    if (!accessToken) {
      const err = new Error('TOKEN_INVALID');
      err.code = 404;
      throw err;
    }

    if (accessToken.expiresAt < new Date()) {
      const err = new Error('TOKEN_EXPIRED');
      err.code = 410;
      throw err;
    }

    if (accessToken.userId !== user.id) {
      const err = new Error('UNAUTHORIZED');
      err.code = 403;
      throw err;
    }

    const pendingLink = await prisma.waliSantri.findFirst({
      where: { userId: accessToken.userId, status: 'pending' },
      include: { student: true },
    });

    if (!pendingLink) {
      const err = new Error('LINK_NOT_FOUND');
      err.code = 404;
      throw err;
    }

    return await prisma.$transaction(async (tx) => {
      await tx.secureAccessToken.update({
        where: { id: accessToken.id },
        data: { expiresAt: new Date() }, // invalidate token
      });

      if (action === 'approve') {
        await tx.waliSantri.update({
          where: { id: pendingLink.id },
          data: { status: 'active', verifiedAt: new Date(), verifiedBy: user.id },
        });

        await tx.notification.create({
          data: {
            userId: pendingLink.userId,
            type: 'wali_approved',
            title: 'Persetujuan Wali Diterima',
            message: `${user.name} telah menyetujui Anda menjadi ${pendingLink.role} dari ${pendingLink.student.name}.`,
            data: JSON.stringify({ studentId: pendingLink.studentId }),
          },
        });

        return { approved: true, student: pendingLink.student, note };
      } else {
        const requesterId = pendingLink.userId;
        const studentName = pendingLink.student.name;

        await tx.waliSantri.delete({ where: { id: pendingLink.id } });

        await tx.notification.create({
          data: {
            userId: requesterId,
            type: 'wali_rejected',
            title: 'Persetujuan Wali Ditolak',
            message: `Permintaan menjadi wali ${pendingLink.role} dari ${studentName} ditolak.`,
          },
        });

        return { approved: false, student: pendingLink.student, note };
      }
    });
  }

  // ── Remove link ────────────────────────────────────────────────────────

  async removeLink(waliSantriId, user, isAdmin = false) {
    const link = await prisma.waliSantri.findUnique({
      where: { id: waliSantriId },
      include: { student: true },
    });

    if (!link) {
      const err = new Error('LINK_NOT_FOUND');
      err.code = 404;
      throw err;
    }

    if (!isAdmin && link.userId !== user.id) {
      const err = new Error('UNAUTHORIZED');
      err.code = 403;
      throw err;
    }

    // Cek apakah ini satu-satunya wali aktif
    if (link.status === 'active') {
      const activeCount = await prisma.waliSantri.count({
        where: { studentId: link.studentId, status: 'active' },
      });
      if (activeCount <= 1) {
        const err = new Error('CANNOT_REMOVE_LAST_WALI');
        err.code = 409;
        throw err;
      }
    }

    await prisma.waliSantri.delete({ where: { id: waliSantriId } });
    return { removed: true };
  }

  // ── Dashboard ───────────────────────────────────────────────────────────

  async getDashboard(user) {
    const links = await prisma.waliSantri.findMany({
      where: { userId: user.id, status: 'active' },
      include: {
        student: {
          include: {
            school: { select: { id: true, name: true } },
            _count: { select: { waliSantri: true } },
          },
        },
      },
    });

    const today = new Date().toISOString().slice(0, 10);
    const studentIds = links.map((l) => l.studentId);

    const todayAttendances = await prisma.studentAttendance.findMany({
      where: {
        studentId: { in: studentIds },
        attendanceDate: { gte: new Date(`${today}T00:00:00Z`) },
      },
    });

    const unreadNotifications = await prisma.notification.count({
      where: { userId: user.id, isRead: false },
    });

    return {
      total_students: links.length,
      students: links.map((link) => ({
        id: link.student.id,
        name: link.student.name,
        nik: link.student.nik,
        role: link.role,
        is_primary: link.isPrimary,
        school: link.student.school?.name || null,
        total_walis: link.student._count.waliSantri,
        today_attendance: todayAttendances.find((a) => a.studentId === link.studentId)?.status || null,
      })),
      summary: {
        total_students: links.length,
        total_walis_count: links.filter((l) => l.isPrimary).length,
        unread_notifications: unreadNotifications,
        hadir: todayAttendances.filter((a) => a.status === 'hadir').length,
        izin: todayAttendances.filter((a) => a.status === 'izin').length,
        sakit: todayAttendances.filter((a) => a.status === 'sakit').length,
        alpa: todayAttendances.filter((a) => a.status === 'alpa').length,
      },
    };
  }
}

module.exports = new WaliSantriService();
```

```javascript
// src/services/auth.service.js

const { PrismaClient } = require('@prisma/client');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');

const prisma = new PrismaClient();

class AuthService {
  // ── Register wali ───────────────────────────────────────────────────────

  async register(data) {
    const existingUser = await prisma.user.findUnique({
      where: { email: data.email },
    });

    if (existingUser) {
      const err = new Error('EMAIL_ALREADY_EXISTS');
      err.code = 409;
      throw err;
    }

    const hashedPassword = await bcrypt.hash(data.password, 12);

    const user = await prisma.user.create({
      data: {
        name: data.name,
        email: data.email,
        password: hashedPassword,
        noKk: data.no_kk || null,
        nikWali: data.nik_wali || null,
        noHp: data.no_hp || null,
        hubungan: data.hubungan || 'wali',
        isWali: true,
        isActive: true,
      },
    });

    const token = this.generateJwt(user);

    return { user: this.formatUser(user), token };
  }

  // ── Login ───────────────────────────────────────────────────────────────

  async login(email, password) {
    const user = await prisma.user.findUnique({ where: { email } });

    if (!user) {
      const err = new Error('INVALID_CREDENTIALS');
      err.code = 401;
      throw err;
    }

    if (!user.isActive) {
      const err = new Error('ACCOUNT_DISABLED');
      err.code = 403;
      throw err;
    }

    const validPassword = await bcrypt.compare(password, user.password);
    if (!validPassword) {
      const err = new Error('INVALID_CREDENTIALS');
      err.code = 401;
      throw err;
    }

    const token = this.generateJwt(user);

    return { user: this.formatUser(user), token };
  }

  // ── Google OAuth ───────────────────────────────────────────────────────

  async google({ googleId, email, name }) {
    let user = await prisma.user.findFirst({
      where: { OR: [{ email }, { googleId }] },
    });

    if (user) {
      if (!user.googleId) {
        await prisma.user.update({
          where: { id: user.id },
          data: { googleId, emailVerifiedAt: new Date() },
        });
      }
    } else {
      user = await prisma.user.create({
        data: {
          name,
          email,
          googleId,
          password: null,
          isWali: true,
          isActive: true,
          emailVerifiedAt: new Date(),
        },
      });
    }

    const token = this.generateJwt(user);

    return {
      user: this.formatUser(user),
      token,
      isNewUser: !user.googleId,
    };
  }

  // ── Helpers ──────────────────────────────────────────────────────────────

  generateJwt(user) {
    return jwt.sign(
      {
        sub: user.id,
        email: user.email,
        name: user.name,
        type: 'mobile_api',
      },
      process.env.JWT_SECRET,
      { expiresIn: process.env.JWT_EXPIRES_IN || '30d' }
    );
  }

  formatUser(user) {
    return {
      id: user.id,
      name: user.name,
      email: user.email,
      noKk: user.noKk,
      noHp: user.noHp,
      hubungan: user.hubungan,
      isWali: user.isWali,
    };
  }
}

module.exports = new AuthService();
```

### 6.4 Controllers

```javascript
// src/controllers/auth.controller.js

const authService = require('../services/auth.service');
const Joi = require('../validators');

class AuthController {
  // POST /api/mobile/v1/auth/register
  async register(req, res, next) {
    try {
      const { error, value } = Joi.registerSchema.validate(req.body, {
        abortEarly: false,
      });
      if (error) {
        return res.status(422).json({
          success: false,
          error: {
            code: 'VALIDATION_ERROR',
            message: 'Data yang Anda masukkan tidak valid.',
            details: error.details.map((e) => ({ field: e.path.join('.'), message: e.message })),
          },
        });
      }

      const result = await authService.register(value);

      return res.status(201).json({
        success: true,
        message: 'Registrasi berhasil. Selamat datang!',
        data: {
          user: result.user,
          access_token: result.token,
          token_type: 'Bearer',
          expires_in: '2592000', // 30 days in seconds
        },
      });
    } catch (err) {
      next(err);
    }
  }

  // POST /api/mobile/v1/auth/login
  async login(req, res, next) {
    try {
      const { error, value } = Joi.loginSchema.validate(req.body);
      if (error) {
        return res.status(422).json({
          success: false,
          error: {
            code: 'VALIDATION_ERROR',
            message: 'Email dan password diperlukan.',
          },
        });
      }

      const result = await authService.login(value.email, value.password);

      return res.status(200).json({
        success: true,
        message: 'Login berhasil.',
        data: {
          user: result.user,
          access_token: result.token,
          token_type: 'Bearer',
          expires_in: '2592000',
        },
      });
    } catch (err) {
      next(err);
    }
  }

  // POST /api/mobile/v1/auth/google
  async google(req, res, next) {
    try {
      const { error, value } = Joi.googleSchema.validate(req.body);
      if (error) {
        return res.status(422).json({
          success: false,
          error: { code: 'VALIDATION_ERROR', message: 'Data Google tidak valid.' },
        });
      }

      const result = await authService.google(value);

      return res.status(200).json({
        success: true,
        message: result.isNewUser ? 'Akun berhasil dibuat dengan Google.' : 'Login Google berhasil.',
        data: {
          user: result.user,
          access_token: result.token,
          token_type: 'Bearer',
          expires_in: '2592000',
        },
      });
    } catch (err) {
      next(err);
    }
  }

  // POST /api/mobile/v1/auth/logout
  async logout(req, res) {
    // Client-side: hapus token dari storage.
    // Server-side: JWT tidak di-blacklist (stateless).
    return res.status(200).json({
      success: true,
      message: 'Logout berhasil.',
    });
  }

  // GET /api/mobile/v1/auth/me
  async me(req, res, next) {
    try {
      const { PrismaClient } = require('@prisma/client');
      const prisma = new PrismaClient();

      const user = await prisma.user.findUnique({
        where: { id: req.user.id },
        select: {
          id: true,
          name: true,
          email: true,
          noKk: true,
          nikWali: true,
          noHp: true,
          hubungan: true,
          isWali: true,
          emailVerifiedAt: true,
          createdAt: true,
        },
      });

      const links = await prisma.waliSantri.findMany({
        where: { userId: req.user.id, status: 'active' },
        include: {
          student: {
            select: { id: true, name: true, nik: true },
          },
        },
      });

      return res.status(200).json({
        success: true,
        data: {
          user,
          students: links.map((l) => ({
            id: l.student.id,
            name: l.student.name,
            nik: l.student.nik,
            role: l.role,
            is_primary: l.isPrimary,
          })),
        },
      });
    } catch (err) {
      next(err);
    }
  }
}

module.exports = new AuthController();
```

```javascript
// src/controllers/student.controller.js

const waliSantriService = require('../services/wali-santri.service');
const Joi = require('../validators');

class StudentController {
  // GET /api/mobile/v1/santri
  async index(req, res, next) {
    try {
      const { PrismaClient } = require('@prisma/client');
      const prisma = new PrismaClient();

      const links = await prisma.waliSantri.findMany({
        where: { userId: req.user.id, status: 'active' },
        include: {
          student: {
            include: { school: { select: { id: true, name: true } } },
          },
        },
      });

      const students = links.map((link) => {
        const s = link.student;
        return {
          id: s.id,
          nik: s.nik,
          name: s.name,
          gender: s.gender,
          birth_date: s.birthDate?.toISOString().slice(0, 10),
          birth_place: s.birthPlace,
          role: link.role,
          is_primary: link.isPrimary,
          school: s.school ? { id: s.school.id, name: s.school.name } : null,
        };
      });

      return res.status(200).json({
        success: true,
        data: { students, total: students.length },
      });
    } catch (err) {
      next(err);
    }
  }

  // GET /api/mobile/v1/santri/:id
  async show(req, res, next) {
    try {
      const { PrismaClient } = require('@prisma/client');
      const prisma = new PrismaClient();

      const link = await prisma.waliSantri.findFirst({
        where: { userId: req.user.id, studentId: req.params.id, status: 'active' },
        include: {
          student: {
            include: {
              school: { select: { id: true, name: true } },
            },
          },
        },
      });

      if (!link) {
        return res.status(404).json({
          success: false,
          error: { code: 'STUDENT_NOT_FOUND', message: 'Santri tidak ditemukan.' },
        });
      }

      const s = link.student;

      // Ambil wali lain
      const otherWalis = await prisma.waliSantri.findMany({
        where: { studentId: s.id, status: 'active', userId: { not: req.user.id } },
        include: { user: { select: { id: true, name: true, email: true, noHp: true } } },
      });

      return res.status(200).json({
        success: true,
        data: {
          id: s.id,
          nik: s.nik,
          name: s.name,
          gender: s.gender,
          birth_date: s.birthDate?.toISOString().slice(0, 10),
          birth_place: s.birthPlace,
          no_kk: s.noKk,
          address: s.address,
          mobile_phone: s.mobilePhone,
          role: link.role,
          is_primary: link.isPrimary,
          school: s.school ? { id: s.school.id, name: s.school.name } : null,
          other_walis: otherWalis.map((w) => ({
            id: w.user.id,
            name: w.user.name,
            role: w.role,
            is_primary: w.isPrimary,
          })),
        },
      });
    } catch (err) {
      next(err);
    }
  }

  // POST /api/mobile/v1/santri
  async store(req, res, next) {
    try {
      const { error, value } = Joi.registerStudentSchema.validate(req.body, {
        abortEarly: false,
      });
      if (error) {
        return res.status(422).json({
          success: false,
          error: {
            code: 'VALIDATION_ERROR',
            message: 'Data tidak valid.',
            details: error.details.map((e) => ({ field: e.path.join('.'), message: e.message })),
          },
        });
      }

      const result = await waliSantriService.registerStudentAndLink(value, req.user);

      const statusCode = result.alreadyLinked ? 200 : 201;
      const message = result.alreadyLinked
        ? 'Santri sudah terhubung dengan akun Anda.'
        : 'Santri berhasil terdaftar dan terhubung dengan akun wali.';

      return res.status(statusCode).json({
        success: true,
        message,
        data: {
          student: {
            id: result.student.id,
            nik: result.student.nik,
            name: result.student.name,
            gender: result.student.gender,
          },
          wali_santri: {
            id: result.waliSantri.id,
            role: result.waliSantri.role,
            is_primary: result.waliSantri.isPrimary,
          },
        },
      });
    } catch (err) {
      next(err);
    }
  }

  // POST /api/mobile/v1/santri/verify-nik
  async verifyNik(req, res, next) {
    try {
      const nik = req.body.nik;

      if (!nik || !/^\d{16}$/.test(nik)) {
        return res.status(422).json({
          success: false,
          error: { code: 'INVALID_NIK_FORMAT', message: 'NIK harus 16 digit angka.' },
        });
      }

      const result = await waliSantriService.checkNikAvailability(nik, req.user.id);

      return res.status(200).json({
        success: true,
        data: {
          nik,
          status: result.exists ? result.status : 'available',
          student_name: result.studentName || null,
          message: result.exists
            ? result.status === 'already_linked_to_you'
              ? 'NIK ini sudah terhubung dengan akun Anda.'
              : 'NIK ini sudah terdaftar di sistem.'
            : 'NIK ini belum terdaftar. Bisa digunakan untuk registrasi Santi baru.',
          suggestion: result.suggestion || null,
        },
      });
    } catch (err) {
      next(err);
    }
  }
}

module.exports = new StudentController();
```

```javascript
// src/controllers/wali-santri.controller.js

const waliSantriService = require('../services/wali-santri.service');
const Joi = require('../validators');

class WaliSantriController {
  // POST /api/mobile/v1/wali-santri/request
  async requestWaliRole(req, res, next) {
    try {
      const { error, value } = Joi.requestWaliRoleSchema.validate(req.body, {
        abortEarly: false,
      });
      if (error) {
        return res.status(422).json({
          success: false,
          error: {
            code: 'VALIDATION_ERROR',
            message: 'Data tidak valid.',
            details: error.details.map((e) => ({ field: e.path.join('.'), message: e.message })),
          },
        });
      }

      const result = await waliSantriService.requestLinkToStudent(value, req.user);

      const statusCode = result.needsApproval ? 202 : (result.alreadyLinked ? 200 : 201);

      return res.status(statusCode).json({
        success: true,
        message: result.message,
        data: {
          student: { id: result.student.id, name: result.student.name },
          wali_santri: result.waliSantri
            ? { id: result.waliSantri.id, role: result.waliSantri.role, status: result.waliSantri.status }
            : null,
          approval_token: result.approvalToken || null,
        },
      });
    } catch (err) {
      next(err);
    }
  }

  // PUT /api/mobile/v1/wali-santri/requests/:token
  async approveReject(req, res, next) {
    try {
      const { action, note } = req.body;
      if (!action || !['approve', 'reject'].includes(action)) {
        return res.status(422).json({
          success: false,
          error: { code: 'VALIDATION_ERROR', message: 'Action harus approve atau reject.' },
        });
      }

      const result = await waliSantriService.approveRejectRequest(
        req.params.token,
        req.user,
        action,
        note
      );

      return res.status(200).json({
        success: true,
        message: result.approved ? 'Permintaan telah disetujui.' : 'Permintaan telah ditolak.',
        data: {
          student: { id: result.student.id, name: result.student.name },
        },
      });
    } catch (err) {
      next(err);
    }
  }

  // DELETE /api/mobile/v1/wali-santri/:id
  async destroy(req, res, next) {
    try {
      await waliSantriService.removeLink(req.params.id, req.user);

      return res.status(200).json({
        success: true,
        message: 'Hubungan wali-Santi berhasil dilepas.',
      });
    } catch (err) {
      next(err);
    }
  }
}

module.exports = new WaliSantriController();
```

### 6.5 Entry Point + Routes

```javascript
// src/index.js

require('dotenv').config();

const express = require('express');
const { authenticate } = require('./middleware/auth.middleware');
const { errorHandler } = require('./middleware/error.middleware');
const v1Routes = require('./routes/v1.routes');

const app = express();

// ── Body parser ──────────────────────────────────────────────────────────
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// ── CORS ─────────────────────────────────────────────────────────────────
app.use((req, res, next) => {
  res.header('Access-Control-Allow-Origin', '*');
  res.header('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization');
  res.header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
  if (req.method === 'OPTIONS') return res.sendStatus(204);
  next();
});

// ── Health check ─────────────────────────────────────────────────────────
app.get('/health', (req, res) => res.json({ status: 'ok', ts: Date.now() }));

// ── API v1 ─────────────────────────────────────────────────────────────────
app.use('/api/mobile/v1', v1Routes);

// ── 404 ───────────────────────────────────────────────────────────────────
app.use((req, res) => {
  res.status(404).json({ success: false, error: { code: 'NOT_FOUND', message: 'Endpoint tidak ditemukan.' } });
});

// ── Error handler ──────────────────────────────────────────────────────────
app.use(errorHandler);

const PORT = process.env.PORT || 3001;
app.listen(PORT, () => {
  console.log(`✅ ALIM Mobile API running on port ${PORT}`);
});
```

```javascript
// src/routes/v1.routes.js

const express = require('express');
const router = express.Router();
const { authenticate } = require('../middleware/auth.middleware');
const authController = require('../controllers/auth.controller');
const studentController = require('../controllers/student.controller');
const waliSantriController = require('../controllers/wali-santri.controller');

// ── Auth (public) ──────────────────────────────────────────────────────────
router.post('/auth/register', authController.register.bind(authController));
router.post('/auth/login', authController.login.bind(authController));
router.post('/auth/google', authController.google.bind(authController));
router.post('/auth/logout', authController.logout.bind(authController));

// ── Auth (protected) ───────────────────────────────────────────────────────
router.get('/auth/me', authenticate, authController.me.bind(authController));

// ── Santri (protected) ────────────────────────────────────────────────────
router.get('/santri', authenticate, studentController.index.bind(studentController));
router.get('/santri/:id', authenticate, studentController.show.bind(studentController));
router.post('/santri', authenticate, studentController.store.bind(studentController));
router.post('/santri/verify-nik', authenticate, studentController.verifyNik.bind(studentController));

// ── Wali-Santri (protected) ───────────────────────────────────────────────
router.post('/wali-santri/request', authenticate, waliSantriController.requestWaliRole.bind(waliSantriController));
router.put('/wali-santri/requests/:token', authenticate, waliSantriController.approveReject.bind(waliSantriController));
router.delete('/wali-santri/:id', authenticate, waliSantriController.destroy.bind(waliSantriController));

// ── Dashboard ─────────────────────────────────────────────────────────────
const dashboardController = require('../controllers/dashboard.controller');
router.get('/dashboard', authenticate, dashboardController.index.bind(dashboardController));
router.get('/dashboard/attendance', authenticate, dashboardController.attendance.bind(dashboardController));

module.exports = router;
```

### 6.6 Validators

```javascript
// src/validators/index.js

const Joi = require('joi');

const registerSchema = Joi.object({
  name: Joi.string().min(2).max(100).required(),
  email: Joi.string().email().required(),
  password: Joi.string().min(8).max(128).required(),
  no_kk: Joi.string().pattern(/^\d{16}$/).allow(null, ''),
  nik_wali: Joi.string().pattern(/^\d{16}$/).allow(null, ''),
  no_hp: Joi.string().min(10).max(20).allow(null, ''),
  hubungan: Joi.string().valid('ayah', 'ibu', 'kakek', 'nenek', 'wali', 'lainnya').default('wali'),
});

const loginSchema = Joi.object({
  email: Joi.string().email().required(),
  password: Joi.string().required(),
});

const googleSchema = Joi.object({
  google_id: Joi.string().required(),
  email: Joi.string().email().required(),
  name: Joi.string().min(2).required(),
  id_token: Joi.string().optional(),
});

const registerStudentSchema = Joi.object({
  nik: Joi.string().length(16).pattern(/^\d{16}$/).required(),
  name: Joi.string().min(2).max(255).required(),
  gender: Joi.string().valid('L', 'P').required(),
  birth_place: Joi.string().max(100).allow(null, ''),
  birth_date: Joi.string().isoDate().allow(null),
  no_kk: Joi.string().pattern(/^\d{16}$/).allow(null, ''),
  role: Joi.string().valid('ayah', 'ibu', 'kakek', 'nenek', 'wali', 'lainnya').default('wali'),
});

const requestWaliRoleSchema = Joi.object({
  nik_santri: Joi.string().length(16).pattern(/^\d{16}$/).required(),
  role: Joi.string().valid('ayah', 'ibu', 'kakek', 'nenek', 'wali', 'lainnya').required(),
  no_kk: Joi.string().pattern(/^\d{16}$/).allow(null, ''),
});

module.exports = {
  registerSchema,
  loginSchema,
  googleSchema,
  registerStudentSchema,
  requestWaliRoleSchema,
};
```

---

## 7. REKOMENDASI: INTEGRASI KE LARAVEL (OPSI A)

### 7.1 Migration untuk tabel baru

File sudah dibuat: `database/migrations/2026_05_29_..._add_wali_santri_tables.php`

```php
// Jalankan:
php artisan migrate
```

### 7.2 Model Baru

```php
// app/Models/WaliSantri.php
// app/Models/WaliRegistrationToken.php
```

### 7.3 Service + Controller (sudah dibuat di project)

```
app/Http/Controllers/Api/Mobile/V1/
├── AuthController.php        ✅
├── StudentController.php    ✅
├── WaliSantriController.php ✅
└── DashboardController.php   ✅

app/Services/
├── WaliSantriService.php    ✅ (termasuk validateNikFormat, checkNikAvailability)
```

### 7.4 Route Registration

```php
// routes/api.php — tambahkan:
Route::prefix('mobile/v1')->group(function () {
    require base_path('routes/mobile-v1.php');
});
```

```php
// routes/mobile-v1.php
<?php
use App\Http\Controllers\Api\Mobile\V1\AuthController;
use App\Http\Controllers\Api\Mobile\V1\StudentController;
use App\Http\Controllers\Api\Mobile\V1\WaliSantriController;
use App\Http\Controllers\Api\Mobile\V1\DashboardController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/google', [AuthController::class, 'google']);
Route::post('/auth/logout', [AuthController::class, 'logout']);

// Protected routes (with JWT middleware)
Route::middleware('auth:api')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/me', [AuthController::class, 'updateProfile']);

    Route::get('/santri', [StudentController::class, 'index']);
    Route::get('/santri/{id}', [StudentController::class, 'show']);
    Route::post('/santri', [StudentController::class, 'store']);
    Route::post('/santri/verify-nik', [StudentController::class, 'verifyNik']);

    Route::post('/wali-santri/request', [WaliSantriController::class, 'requestWaliRole']);
    Route::put('/wali-santri/requests/{token}', [WaliSantriController::class, 'approveReject']);
    Route::delete('/wali-santri/{id}', [WaliSantriController::class, 'destroy']);

    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/attendance', [DashboardController::class, 'attendance']);
});
```

### 7.5 Middleware Auth (Laravel)

```php
// app/Http/Middleware/AuthenticateApi.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthenticateApi
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user || !$user->is_active) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'Token tidak valid atau akun tidak aktif.',
                    ],
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TOKEN_INVALID',
                    'message' => 'Token autentikasi diperlukan.',
                ],
            ], 401);
        }

        return $next($request);
    }
}
```

### 7.6 Error Handler Global

```php
// app/Exceptions/Handler.php — override render()

public function render($request, Throwable $e)
{
    // API request → JSON error
    if ($request->is('api/mobile/*') || $request->wantsJson()) {
        $code = $e->getCode() ?: 500;
        $validCodes = [400, 401, 403, 404, 409, 422, 500];
        if (!in_array($code, $validCodes)) $code = 500;

        return response()->json([
            'success' => false,
            'error' => [
                'code' => class_basename($e),
                'message' => $e->getMessage(),
            ],
        ], $code);
    }

    return parent::render($request, $e);
}
```

---

## 8. PANDUAN MOBILE APP (Flutter / React Native)

### 8.1 Authentication Flow

```
┌─────────────────────────────────────────────────────────────┐
│                   MOBILE APP AUTH FLOW                      │
│                                                             │
│  ┌──────────┐    ┌──────────────┐    ┌─────────────────┐  │
│  │ Splash   │───→│ Check Token  │───→│ Token valid?    │  │
│  │ Screen   │    │ from storage │    │                 │  │
│  └──────────┘    └──────────────┘    └────────┬────────┘  │
│                              │ no              │ yes        │
│                              ▼                 ▼            │
│                    ┌──────────────┐    ┌────────────────┐   │
│                    │ Login/Reg    │    │  Dashboard     │   │
│                    │ Screen       │    │  (Home)        │   │
│                    └──────┬───────┘    └────────────────┘   │
│                           │                                   │
│              ┌────────────┼────────────┐                      │
│              ▼            ▼            ▼                      │
│     ┌────────────┐ ┌────────────┐ ┌────────────┐            │
│     │ Email/Pass │ │ Google     │ │ Forgot     │            │
│     │ Login      │ │ OAuth      │ │ Password   │            │
│     └─────┬──────┘ └─────┬──────┘ └─────┬──────┘            │
│           │              │              │                    │
│           └──────────────┴──────────────┘                    │
│                         │                                    │
│                         ▼                                    │
│                   ┌────────────────┐                        │
│                   │ Store JWT      │                        │
│                   │ → SharedPrefs   │                        │
│                   └───────┬────────┘                        │
│                           │                                 │
│                           ▼                                 │
│              ┌────────────────────────┐                    │
│              │ Register: Input No KK   │                    │
│              │ Input NIK Santi         │                    │
│              │ OR: Request to existing │                    │
│              │ Santi via NIK search    │                    │
│              └────────────────────────┘                    │
└─────────────────────────────────────────────────────────────┘
```

### 8.2 State Management

```dart
// Flutter: GetX atau Riverpod

// AuthState
class AuthState {
  User? user;
  List<Student> students;
  String? token;

  bool get isLoggedIn => token != null && user != null;
  bool get needsNikSetup => user != null && user!.noKk == null;
}

// StudentState
class StudentState {
  List<Student> students; // semua anak dari semua wali
  Map<String, List<Attendance>> attendances;
}
```

### 8.3 API Client Base

```dart
class ApiClient {
  final Dio _dio;
  String? _token;

  Future<void> setToken(String? token) async {
    _token = token;
  }

  Map<String, String> get _headers => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    if (_token != null) 'Authorization': 'Bearer $_token',
  };

  Future<ApiResponse> get(String path) async { ... }
  Future<ApiResponse> post(String path, Map<String, dynamic> body) async { ... }
}
```

---

## 9. RINGKASAN ERROR CODES

| Code | HTTP | Keterangan |
|------|------|------------|
| `VALIDATION_ERROR` | 422 | Input tidak valid |
| `UNAUTHORIZED` | 401 | Token tidak valid |
| `TOKEN_EXPIRED` | 401 | Token kadaluarsa |
| `ACCOUNT_DISABLED` | 403 | Akun tidak aktif |
| `EMAIL_ALREADY_EXISTS` | 409 | Email sudah terdaftar |
| `NIK_ALREADY_EXISTS` | 409 | NIK sudah didaftarkan orang lain |
| `INVALID_NIK_FORMAT` | 422 | Format NIK tidak valid |
| `KK_MISMATCH` | 422 | No KK tidak cocok |
| `STUDENT_NOT_FOUND` | 404 | Santi tidak ditemukan |
| `LINK_NOT_FOUND` | 404 | Hubungan wali-santi tidak ditemukan |
| `DUPLICATE_REQUEST` | 409 | Request sudah ada |
| `MAX_WALI_EXCEEDED` | 409 | Maksimal wali tercapai |
| `TOKEN_INVALID` | 404 | Token otorisasi invalid |
| `TOKEN_EXPIRED` | 410 | Token otorisasi kadaluarsa |
| `CANNOT_REMOVE_LAST_WALI` | 409 | Tidak bisa hapus wali terakhir |