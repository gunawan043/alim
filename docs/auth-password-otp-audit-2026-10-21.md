# Audit Sistem Lupa Password & OTP — 2026-10-21

## Temuan utama

1. Tabel `password_otps` tidak memiliki kolom `attempts`, `used_at`, dan `last_sent_at`, tetapi controller lama memakai `is_used` yang tidak ada.
2. OTP dapat diverifikasi berulang kali.
3. Form reset dapat diakses dengan session user tanpa benar-benar lolos OTP.
4. Email yang tidak terdaftar dibedakan dari email terdaftar (email enumeration).
5. Tidak ada pembatasan request OTP, resend, atau percobaan OTP.
6. Email gagal dikirim tetap dilaporkan sebagai berhasil.
7. Session tidak diregenerasi setelah verifikasi OTP.
8. Reset password tidak membuka akun yang sedang terkunci karena percobaan login gagal.
9. Tidak ada route password update/confirm yang sesuai.
10. Mobile forgot password masih memakai validasi `exists` dan tanpa rate limit.

## Perbaikan

- Migration baru menambahkan `attempts`, `used_at`, `last_sent_at`.
- OTP:
  - disimpan sebagai hash,
  - berlaku 10 menit,
  - hanya bisa dipakai sekali,
  - maksimal 5 percobaan,
  - rate limit request (per email/IP dan per email), resend, dan endpoint cek.
- Respons email selalu generik untuk mencegah enumerasi.
- Session:
  - OTP verification meregenerasi session ID,
  - reset hanya boleh jika session OTP terverifikasi dan OTP `used_at` masih valid,
  - session diinvalidasi setelah reset.
- Reset password:
  - memakai aturan password Laravel,
  - mengosongkan failed login/lockout,
  - mengganti remember token,
  - mencatat AuditLog.
- Form OTP:
  - email ditampilkan,
  - tombol kirim ulang dan batal,
  - timer kedaluwarsa.
- Route `forgot-password` lama diarahkan ke OTP.
- Route update password dan konfirmasi password ditambahkan.
- Mobile:
  - respons generik,
  - rate limit,
  - event PasswordReset.
- Bug transaction pada percobaan OTP diperbaiki agar increment attempts tidak rollback.

## Flow

1. `/password/forgot` → kirim OTP ke email.
2. `/password/otp` → verifikasi 6 digit.
3. `/password/reset` → buat password baru.
4. Login dengan password baru.
