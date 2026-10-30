# Push Notification — FCM + APNs Langsung

ALIM memakai push notification native. Backend mengirim langsung ke:

- **FCM HTTP v1** untuk Android
- **APNs token-based** untuk iOS

Keduanya tidak menagih per notifikasi. FCM gratis. APNs gratis, tetapi
membutuhkan Apple Developer Program ($99/tahun; lembaga pendidikan dapat
mengajukan fee waiver).

## Pengaturan Super Admin

Buka **Super Admin → Pengaturan Sistem → Push Mobile**.

### FCM

Isi:

- **Firebase Project ID**
- **Service Account JSON** dari Firebase Console → Project settings →
  Service accounts → Generate new private key

Service account disimpan terenkripsi di `system_settings`. Jangan menaruh
service account di aplikasi mobile.

### APNs

Isi:

- **Key ID** dari file `AuthKey_XXXXXXXXXX.p8`
- **Team ID** Apple Developer
- **Bundle ID** (harus sama dengan `com.alim.app`)
- **Environment**: `sandbox` untuk development build, `production` untuk
  TestFlight/App Store
- **Private Key (.p8)**

Private key disimpan terenkripsi.

Environment fallback juga tersedia di `.env`:

```dotenv
FCM_PROJECT_ID=
FCM_SERVICE_ACCOUNT_JSON=
FCM_SERVICE_ACCOUNT_PATH=
APNS_KEY_ID=
APNS_TEAM_ID=
APNS_BUNDLE_ID=com.alim.app
APNS_PRIVATE_KEY=
APNS_PRIVATE_KEY_PATH=
APNS_ENVIRONMENT=production
```

## Notifikasi nilai

- Publikasi manual mengirim event `nilai_published`.
- Auto publish pertama mengirim `nilai_published`.
- Perubahan nilai setelah buku published mengirim `nilai_updated`.
- Update dari autosave dibatasi satu notifikasi per buku per 60 detik agar
  satu sesi edit tidak menghasilkan puluhan push. Notifikasi in-app terbaru
  tetap dapat dilihat kapan saja.

## Kontrak API perangkat

Mobile mengirim token native dari
`expo-notifications.getDevicePushTokenAsync()`.

```http
POST /api/mobile/v1/devices
Authorization: Bearer <sanctum_token>
Content-Type: application/json
```

Android:

```json
{
  "push_token": "<FCM registration token>",
  "provider": "fcm",
  "platform": "android",
  "device_name": "Samsung A54",
  "app_version": "1.0.0"
}
```

iOS:

```json
{
  "push_token": "<APNs device token>",
  "provider": "apns",
  "platform": "ios",
  "device_name": "iPhone 15",
  "app_version": "1.0.0"
}
```

Endpoint bersifat idempotent berdasarkan `push_token`. Saat logout, aplikasi
memanggil `DELETE /api/mobile/v1/devices/{device_id}`.

## Format payload FCM dan APNs

Format transport **berbeda**, tetapi data aplikasinya dibuat sama.

### FCM

```json
{
  "message": {
    "token": "<FCM token>",
    "notification": {
      "title": "Nilai Matematika dipublikasikan",
      "body": "Nilai untuk Aisyah sudah dapat dilihat."
    },
    "data": {
      "notification_id": "uuid",
      "module": "academic",
      "action": "nilai_published",
      "screen": "grades",
      "student_id": "uuid",
      "student_name": "Aisyah",
      "book_id": "123",
      "subject_id": "45",
      "study_group_id": "67",
      "semester": "ganjil",
      "academic_year_id": "89"
    },
    "android": {
      "priority": "HIGH",
      "notification": {
        "channel_id": "alim_default",
        "sound": "default"
      }
    }
  }
}
```

FCM mewajibkan semua nilai `data` berupa string.

### APNs

```json
{
  "aps": {
    "alert": {
      "title": "Nilai Matematika dipublikasikan",
      "body": "Nilai untuk Aisyah sudah dapat dilihat."
    },
    "sound": "default"
  },
  "notification_id": "uuid",
  "module": "academic",
  "action": "nilai_published",
  "screen": "grades",
  "student_id": "uuid",
  "student_name": "Aisyah",
  "book_id": "123",
  "subject_id": "45",
  "study_group_id": "67",
  "semester": "ganjil",
  "academic_year_id": "89"
}
```

Header APNs:

```text
apns-topic: com.alim.app
apns-push-type: alert
apns-priority: 10 (high/urgent) atau 5
apns-id: <notification uuid>
```

Di aplikasi, `expo-notifications` menormalkan keduanya menjadi
`notification.request.content.data` dengan key yang sama (`screen`,
`student_id`, dan seterusnya). Karena itu deep link aplikasi tidak perlu tahu
apakah pesan datang dari FCM atau APNs.

## Deep link aplikasi

- `screen: "grades"` → `/nilai`
- `screen: "report"` → `/raport`
- fallback → `/notifikasi/{notification_id}`

Data push tidak dianggap sebagai otorisasi. Aplikasi tetap memanggil API
setelah dibuka untuk memvalidasi akses wali.

## Web Push VAPID (browser)

Web memakai Web Push VAPID langsung, jadi notifikasi tetap muncul saat tab
atau browser ditutup.

### Mengaktifkan

1. Buka **Super Admin → Pengaturan Sistem → Push Mobile → Web Push**.
2. Isi VAPID Subject, misalnya `mailto:admin@sekolah.sch.id`.
3. Klik **Generate VAPID**.
4. Buka halaman web sebagai user, klik ikon lonceng → **Aktifkan Notifikasi Browser**.
5. Izinkan notifikasi pada dialog browser.

Subscription browser disimpan di `web_push_subscriptions`. Key VAPID private
disimpan terenkripsi. Generate ulang key akan membatalkan subscription lama.

### Yang dikirim

Web Push mengirim payload JSON:

```json
{
  "title": "Nilai Matematika diperbarui",
  "body": "Nilai untuk Aisyah telah diperbarui.",
  "icon": "https://.../build/images/alim-sm-light.png",
  "url": "https://.../portal/...",
  "tag": "alim-<notification uuid>",
  "notification_id": "uuid",
  "screen": "grades",
  "student_id": "uuid"
}
```

Service worker `public/sw.js` menampilkan notifikasi dan mengarahkan klik ke
`url`; jika jendela web sudah terbuka, jendela tersebut difokuskan.

Web Push membutuhkan HTTPS. `localhost` dikecualikan untuk development.
Chrome/Edge/Firefox didukung; dukungan Safari bergantung versi OS dan
pemasangan sebagai web app/PWA.

## Build mobile

Remote push tidak berfungsi di Expo Go.

Android:

1. Letakkan `google-services.json` di project mobile atau set
   `GOOGLE_SERVICES_JSON` saat build.
2. Build development/production.
3. Pastikan FCM credentials terpasang di EAS.

iOS:

1. Siapkan APNs Auth Key `.p8`.
2. Upload melalui EAS Credentials.
3. Gunakan environment APNs yang sesuai saat build.

```bash
cd "/Users/wawangunawan/Documents/@ALIM PROJECT/Alim-Mobile"
npx expo prebuild
npx expo run:android
# atau
eas build --profile development --platform android
```

## Retry dan token invalid

- Token FCM/APNs yang ditolak (`UNREGISTERED`, `BadDeviceToken`, HTTP 410)
  otomatis ditandai `revoked_at` di `user_devices`.
- Job push dicoba ulang bila provider aktif gagal.
- Notifikasi in-app di `notifications_universal` tetap tersimpan walaupun
  push gagal.
