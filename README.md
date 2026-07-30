# Presensi Online

Aplikasi web sederhana untuk mencatat **presensi masuk** dan **presensi pulang**
dengan validasi lokasi menggunakan PostGIS. Online-only — tidak ada penyimpanan
presensi offline.

## Fitur

- Login berbasis sesi (Laravel Session) dengan akun dari tabel pengguna yang
  disinkronkan dari aplikasi utama (lihat `users`).
- Halaman pegawai: status koneksi, jam server, status presensi, tombol besar
  Presensi Masuk / Presensi Pulang, akurasi GPS, hasil validasi.
- Validasi backend: akurasi GPS, kesegaran data lokasi, dan geofence PostGIS
  (`ST_Covers` untuk polygon, `ST_DWithin` untuk lingkaran).
- Admin: editor peta Leaflet + Geoman untuk menggambar area lingkaran/polygon,
  aktivasi area, daftar presensi masuk & pulang dengan filter.
- PWA installable (manifest + ikon), network-only.

## Stack

PHP 8.3 · Laravel 11 · PostgreSQL 16 + PostGIS 3 · Blade · Alpine.js ·
Tailwind CSS · Vite · Leaflet + Leaflet-Geoman (Free) · OpenStreetMap.

## Menjalankan dengan Docker

Prasyarat: Docker (dengan Compose). Untuk membangun aset frontend, Node.js 20+
dan npm tersedia di host (untuk `sharp`).

### 1. Siapkan environment

```sh
cp .env.example .env
```

Anda dapat membiarkan nilai default — `docker compose` sudah mengatur koneksi
database ke container `db`.

### 2. Bangun aset frontend (sekali)

```sh
npm install
npm run build        # menghasilkan public/build/ dan public/icons/
```

Jika host Anda tidak punya Node, gunakan container build:

```sh
docker compose run --rm node
```

### 3. Jalankan stack

```sh
docker compose up -d --build
docker compose exec app php artisan key:generate
```

Entrypoint container akan menjalankan `composer install`, menerapkan migrasi,
dan menjalankan seeder secara idempoten. Buka:

```
http://localhost:8080
```

### 4. Akun demo

| Role    | Username  | Password     |
| ------- | --------- | ------------ |
| Admin   | `admin` | `admin123` |
| Pegawai | `budi`  | `budi123`  |
| Pegawai | `siti`  | `siti123`  |

## Konfigurasi

Pengaturan presensi ada di `config/attendance.php` / `.env`:

```
ATTENDANCE_MAX_GPS_ACCURACY_METER=50
ATTENDANCE_MAX_LOCATION_AGE_SECONDS=30
ATTENDANCE_LOCATION_TIMEOUT_MS=15000
ATTENDANCE_RATE_LIMIT_PER_MINUTE=30
DEFAULT_MAP_CENTER_LAT=-6.200100
DEFAULT_MAP_CENTER_LNG=106.816700
DEFAULT_MAP_ZOOM=16
```

## Integrasi data pengguna

Tabel `users` bersifat tersinkron dari aplikasi utama. Aplikasi presensi tidak
menyediakan fitur tambah/edit/hapus pengguna. Sinkronkan baris dari sistem
utama (DB link, job, atau dump) ke tabel `users` berikut minimal:

```
user_id (maps ke users.id)
employee_number
name
role            -- admin | employee
password        -- boleh NULL jika autentikasi delegasi ke SSO
is_active
```

Untuk skenario SSO/API auth, ganti guard `web` dengan custom user provider.

## Endpoint API (semua sesi + CSRF)

```
GET  /api/connection-check
GET  /api/attendance/today
POST /api/attendance/check-in
POST /api/attendance/check-out
GET  /api/admin/attendance
GET  /api/admin/location
PUT  /api/admin/location
PATCH /api/admin/location/{id}/toggle
DELETE /api/admin/location/{id}
```

## Struktur singkat

```
app/
  Http/Controllers/         Auth, Attendance, Admin*, ConnectionCheck
  Http/Middleware/          EnsureUserHasRole, SetLocaleFromConfig
  Repositories/             LocationRepository (PostGIS I/O)
  Services/                 AttendanceService, GeofenceService
  Exceptions/               AttendanceException
database/migrations/        postgis, users, attendance_locations/_records/_attempts
resources/
  css/app.css, js/*.js, views/*.blade.php, icons/icon.svg
public/manifest.webmanifest, public/icons/
docker/                     Dockerfile, nginx, php, entrypoint.sh
```

## Catatan keamanan

Produksi wajib HTTPS, `APP_DEBUG=false`, dan baterai Laravel standar aktif:
session, CSRF, middleware auth + role, parameter binding / Eloquent, rate limit
endpoint presensi, tanpa stack trace di produksi.


Admin	admin	admin@presensi.local	admin123	ADM-001
Pegawai	budi	budi@presensi.local	budi123	EMP-0001
Pegawai	siti	siti@presensi.local	siti123	EMP-0002
