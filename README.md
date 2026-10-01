# Digital Information Board

Aplikasi digital signage internal perusahaan. Admin mengelola konten lewat CMS, lalu konten tampil otomatis di TV/monitor area pintu masuk dengan rotasi tiap 5 detik.

- **Display publik**: `/` (tanpa login)
- **Admin CMS**: `/admin` (butuh login)

---

## Stack

| Komponen | Versi |
|---|---|
| PHP | 8.3 |
| Laravel | 13.x |
| React | 19.x |
| Vite | 8.x |
| Database | **MySQL** (SQLite tidak digunakan) |

Styling memakai CSS tulis-tangan, tanpa Tailwind. Font Poppins di-*self-host* di `public/fonts`.

---

## Instalasi

### 1. Dependency

```bash
composer install
npm install
```

### 2. Konfigurasi environment

Salin `.env.example` menjadi `.env`, lalu isi:

```env
APP_NAME="Digital Information Board"
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=digital_information
DB_USERNAME=root
DB_PASSWORD=
```

Buat database-nya lebih dulu:

```sql
CREATE DATABASE digital_information CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Lalu generate key:

```bash
php artisan key:generate
```

### 3. Database

```bash
php artisan migrate
php artisan db:seed
```

Seeder membuat 8 live channel, 32 host, 32 slot jadwal, dan data contoh. Akun admin default (hanya di `local`/`testing`):

```
username: admin
password: admin
```

Untuk production, buat admin lewat command agar password bebas memilih:

```bash
php artisan admin:create username "Nama Lengkap" email@perusahaan.com
```

> Admin di-seed hanya jika `ADMIN_NAME`, `ADMIN_USERNAME`, `ADMIN_EMAIL`, dan `ADMIN_PASSWORD` terisi di `.env`, atau pada environment `local`/`testing`.

### 4. Symlink storage

```bash
php artisan storage:link
```

Wajib dijalankan di setiap device. Tanpa symlink ini, foto tidak bisa diakses lewat `/storage/...`.

### 5. Jalankan

```bash
npm run dev          # terminal 1 — Vite
php artisan serve    # terminal 2 — Laravel
```

Production build:

```bash
npm run build
php artisan serve
```

---

## Pindah Device

Board bisa dijalankan dari device berbeda tanpa kehilangan foto dan data. Foto ikut git; database dibawa melalui satu file dump.

### Dari device lama

Upload foto lewat admin CMS seperti biasa. Setiap kali ada upload, `database/dumps/database.sql` diperbarui otomatis (kontrol dengan `AUTO_DB_DUMP` di `.env`).

Pastikan dump terbaru sudah dibuat:

```bash
php artisan db:dump
```

Lalu commit dan push:

```bash
git add -A
git commit -m "Update board content"
git push
```

Salin satu file ini ke device baru: **`database/dumps/database.sql`**

> File ini tidak ikut git karena berisi hash password dan.token sesi. Jangan commit atau simpan di tempat publik.

### Di device baru

```bash
git clone <repo> && cd digital-information
composer install
npm install
cp .env.example .env      # lalu sesuaikan DB_* dan APP_KEY
php artisan key:generate
```

Buat database kosong, lalu restore:

```bash
php artisan migrate        # membuat struktur tabel
php artisan db:restore --force
npm run build
php artisan storage:link  # Photos from git live in storage/app/public
php artisan serve
```

Hasilnya identik dengan device lama: foto, channel, host, jadwal, dan Achievements.

### Ringkas

```text
Device lama                          Device baru
───────────                          ───────────
admin upload foto
  → auto-dump                       git clone
  → foto masuk git                   composer install / npm install
  → git push                         php artisan migrate
  → salin database.sql               php artisan db:restore --force
                                     npm run build
                                     php artisan storage:link
```

### Catatan

- `php artisan storage:link` harus dijalankan ulang di device baru. Symlink menunjuk path absolut, jadi tidak pernah ikut pindah.
- `db:restore` menolak jalan bila database sudah berisi data, kecuali diberi `--force`. Perintah ini menghapus isi tabel sebelum mengimpor.
- Foto yang sudah pernah di-commit tidak hilang dari riwayat git meski dihapus dari CMS.

---

## Perintah dump database

```bash
php artisan db:dump                      # → database/dumps/database.sql
php artisan db:dump --force             # timpa dump yang ada
php artisan db:dump --tables=hosts,promotions
php artisan db:dump --output=/tmp/x.sql  # tulis ke path lain
```

```bash
php artisan db:restore                          # dari lokasi default
php artisan db:restore --file=/path/backup.sql
php artisan db:restore --force                  # wajib bila DB sudah berisi data
```

Implementasi memakai PDO, bukan `mysqldump`, sehingga tidak bergantung pada lokasi executable yang berbeda antar device. Tabel `sessions`, `cache`, `jobs`, `failed_jobs`, dan `cache_locks` sengaja dikecualikan: isinya sementara dan tidak relevan untuk board.

---

## Endpoint

### Publik

```
GET  /                    Digital display
GET  /api/display         Data untuk display (tanpa auth)
```

### Admin (butuh session + role admin)

```
GET|POST   /admin/login
POST       /admin/logout
GET        /admin
GET        /admin/{section}
GET        /admin/{resource}/create
GET        /admin/{resource}/{id}/edit

GET|POST   /api/admin/{resource}
GET|PUT    /api/admin/{resource}/{id}
DELETE     /api/admin/{resource}/{id}
PATCH      /api/admin/{resource}/{id}/toggle
GET        /api/admin/dashboard
GET|PUT    /api/admin/live-hosts/board
```

`{resource}` adalah salah satu dari: `promotions`, `achievements`, `birthdays`, `hosts`, `channels`, `weekly-meetings`, `stream-links`.

### Link streaming Host Live

Menu **Link Streaming** (`/admin/stream-links`) menyimpan daftar link statis (misal 3–4 link TikTok per channel). Di **Jadwal Host Live**, setiap slot punya dropdown kedua untuk memilih link mana yang aktif pada tanggal tersebut; dropdown link baru aktif setelah slot diisi host. Display publik menampilkan teks **Link Live Tiktok** yang hyperlink ke URL tersebut beserta QR code yang di-generate di sisi browser dari URL itu.

---

## Testing

```bash
php artisan test
vendor/bin/pint --dirty
```

Suite memakai database terpisah `digital_information_test` sesuai `phpunit.xml`. Buat dulu bila belum ada:

```sql
CREATE DATABASE digital_information_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Auto-dump dimatikan otomatis pada environment `testing` agar data fixture tidak menimpa snapshot asli.

---

## Catatan arsitektur

- **Hanya satu Blade shell** (`resources/views/app.blade.php`) yang menyuntik `data-page` dan `data-props` ke `#app`. `resources/js/app.jsx` memetakan nilai `data-page` ke komponen. Tidak ada react-router — navigasi admin memakai `<a href>` biasa dengan full page load.
- **CRUD generik.** `AdminContentController` melayani 6 resource lewat satu map `RESOURCES`. Validasi inline di `validated()`, tanpa Form Request terpisah.
- **Otorisasi** hanya lewat `EnsureUserIsAdmin` middleware yang mengecek `role === 'admin'`. Tidak ada policy.
- **Dua timer terpisah** di display: rotasi slide tiap 5 detik (`setInterval`) dan polling data tiap 45 detik (`setTimeout` berantai). Keduanya dibersihkan saat unmount.
- **Timers display** hanya dibuat bila ada lebih dari satu slide, sehingga kategori kosong dilewati tanpa menambah beban.