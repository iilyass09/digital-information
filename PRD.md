# PRD — Digital Information Board

## 1. Product Overview

**Digital Information Board** adalah aplikasi web internal perusahaan yang berfungsi sebagai **digital mading / digital signage**.

Aplikasi akan ditampilkan pada TV/monitor di area pintu masuk karyawan. Informasi ditampilkan otomatis dalam bentuk slide fullscreen dan berganti setiap 5 detik.

Aplikasi memiliki dua area:

1. **Admin CMS** — untuk mengelola seluruh konten.
2. **Public Digital Display** — untuk menampilkan konten kepada karyawan tanpa login.

### Core concept

```text
ADMIN
  ↓
Input & manage information
  ↓
Laravel API
  ↓
MySQL
  ↓
React Digital Display
  ↓
TV / Monitor
```

Prioritas produk:

1. Readability pada TV
2. Fullscreen digital signage
3. Automatic rotation
4. Reliable long-running display
5. Easy content management
6. Responsive layout

Aplikasi **bukan sekadar CRUD dashboard**. Halaman public harus terasa seperti digital signage perusahaan.

---

# 2. Technology Stack

Gunakan:

- Backend: Laravel
- Frontend: React
- Build tool: Vite
- Database: **MySQL**
- API: Laravel REST API
- Authentication: Laravel authentication/session
- ORM: Eloquent
- Storage: Laravel Storage
- Styling: gunakan pendekatan yang konsisten dengan project; jika belum ada standar, gunakan CSS/Tailwind sesuai kebutuhan project

### Hard requirement

**Jangan menggunakan SQLite.**

`.env.example` wajib menggunakan:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

---

# 3. Roles & Access

Terdapat dua role:

- `admin`
- `user`

## Admin

Admin harus login.

URL:

```text
/admin/login
/admin
```

Admin dapat mengelola seluruh konten.

## User / Public

User tidak perlu login.

Ketika membuka root domain:

```text
/
```

langsung menampilkan Digital Information Board.

Tidak boleh ada landing page, login page, atau dashboard sebelum display.

---

# 4. URL Structure

## Public

```text
/
```

Digital Information Board.

Public API:

```text
GET /api/display
```

Endpoint tambahan dapat tersedia jika diperlukan:

```text
GET /api/display/promotions
GET /api/display/achievements
GET /api/display/live-hosts
GET /api/display/birthdays
```

## Admin

```text
/admin/login
/admin
/admin/promotions
/admin/achievements
/admin/live-hosts
/admin/birthdays
/admin/display-preview
```

Admin API:

```text
/api/admin/promotions
/api/admin/achievements
/api/admin/live-hosts
/api/admin/birthdays
```

Semua endpoint admin wajib dilindungi authentication dan role middleware.

---

# 5. Main Features

Digital Board memiliki empat kategori informasi:

1. Info Promosi
2. Achievement
3. Jadwal Host Live
4. Birthday

Kategori ditampilkan bergantian.

Default interval:

```text
5 detik
```

Contoh:

```text
0–5 detik     → Promosi
5–10 detik    → Achievement
10–15 detik   → Jadwal Host Live
15–20 detik   → Birthday
20–25 detik   → Promosi
...
```

Jika suatu kategori tidak memiliki data aktif/relevan, kategori tersebut **dilewati**.

Contoh:

```text
Promosi
↓
Live Host
↓
Birthday
↓
Promosi
```

Jika tidak ada data sama sekali, display harus menampilkan fallback yang tetap rapi dan tidak blank.

---

# 6. Feature — Info Promosi

Admin dapat membuat beberapa banner promosi.

## Fields

- title
- image
- description (optional)
- start_date
- end_date
- is_active
- sort_order

## Rules

Promosi hanya dianggap tampil apabila:

- `is_active = true`
- tanggal saat ini berada pada periode promosi, jika periode ditentukan

Admin dapat:

- Create
- Read
- Update
- Delete
- Activate/deactivate
- Upload banner
- Mengatur urutan

## Display

Banner menjadi fokus utama layar.

Gunakan gambar sebesar mungkin tanpa distorsi.

Gunakan pendekatan seperti:

```css
object-fit: cover;
```

sesuai kebutuhan desain.

Jika ada beberapa banner aktif, sediakan carousel internal untuk banner promosi tanpa mengganggu rotasi utama.

---

# 7. Feature — Achievement

Admin dapat memasukkan pencapaian karyawan.

Contoh:

- Absensi paling lengkap
- Penjualan terbanyak
- Employee of the Month
- Host dengan performa terbaik
- Achievement lain

## Fields

- employee_name
- division
- title
- description
- image
- achievement_date
- is_active
- sort_order

## Display

Prioritaskan:

1. Foto
2. Nama
3. Jenis achievement
4. Divisi
5. Deskripsi singkat

Tampilan harus dapat dibaca dari jarak beberapa meter.

---

# 8. Feature — Jadwal Host Live

Perusahaan memiliki 8 channel/game:

1. Johen PUBG
2. Johen MLBB
3. Johen Roblox
4. Johen Valorant
5. Johen Free Fire
6. Johen E-Football
7. Johen FC Mobile
8. Monkey PUBG

Setiap channel memiliki **4 host setiap harinya**.

## Live Channel

Fields:

- name
- logo
- stream_url
- stream_logo
- is_active
- sort_order

Seed default harus membuat 8 channel di atas.

## Live Host Schedule

Slot jadwal milik channel bersifat permanen: tidak ada tanggal, jadi jadwal yang sama
ditampilkan setiap hari.

Fields:

- live_channel_id
- start_time
- end_time
- sort_order

Host per slot disimpan di tabel `live_hosts` (satu baris per slot, `live_schedule_id` unik).

Admin dapat:

- Mengubah host setiap slot
- Mengaktifkan/nonaktifkan jadwal
- Mengatur link streaming (URL + logo) per channel, dipakai otomatis semua slot channel itu

Tidak ada lagi halaman Link Streaming terpisah maupun pemilihan tanggal.

## Display

Untuk layar besar, gunakan grid/card.

Target visual:

```text
┌──────────────┬──────────────┬──────────────┬──────────────┐
│ JOHEN PUBG   │ JOHEN MLBB   │ JOHEN ROBLOX │ JOHEN VALO   │
│ Host 1       │ Host 1       │ Host 1       │ Host 1       │
│ Host 2       │ Host 2       │ Host 2       │ Host 2       │
│ Host 3       │ Host 3       │ Host 3       │ Host 3       │
│ Host 4       │ Host 4       │ Host 4       │ Host 4       │
└──────────────┴──────────────┴──────────────┴──────────────┘

┌──────────────┬──────────────┬──────────────┬──────────────┐
│ FREE FIRE    │ E-FOOTBALL   │ FC MOBILE   │ MONKEY PUBG  │
│ Host 1       │ Host 1       │ Host 1       │ Host 1       │
│ Host 2       │ Host 2       │ Host 2       │ Host 2       │
│ Host 3       │ Host 3       │ Host 3       │ Host 3       │
│ Host 4       │ Host 4       │ Host 4       │ Host 4       │
└──────────────┴──────────────┴──────────────┴──────────────┘
```

Layout harus tetap readable pada berbagai resolusi.

---

# 9. Feature — Birthday

Admin menyimpan data ulang tahun karyawan.

## Fields

- employee_name
- division
- birth_date
- image
- is_active

Birthday tidak disimpan sebagai record yang hanya berlaku satu hari.

Sistem harus menentukan birthday berdasarkan:

```text
MONTH(birth_date) = current month
DAY(birth_date) = current day
```

## Display

Contoh:

```text
🎂 HAPPY BIRTHDAY

[PHOTO]

Ahmad Fauzan
IT Department

30 September
```

Jika beberapa karyawan ulang tahun pada hari yang sama, tampilkan semuanya.

Jika tidak ada birthday hari ini, kategori Birthday dilewati.

---

# 10. Database Design

Gunakan MySQL.

## users

Minimal:

```text
id
name
username
email
password
role
created_at
updated_at
```

Role:

```text
admin
user
```

Public user tidak harus dibuat sebagai account. Role `user` hanya disediakan jika diperlukan untuk pengembangan berikutnya.

## promotions

```text
id
title
image
description
start_date
end_date
is_active
sort_order
created_at
updated_at
```

## achievements

```text
id
employee_name
division
title
description
image
achievement_date
is_active
sort_order
created_at
updated_at
```

## live_channels

```text
id
name
is_active
sort_order
created_at
updated_at
```

## live_hosts

Satu baris = satu slot jadwal yang berlaku permanen setiap hari, tanpa kolom tanggal.
`live_schedule_id` unik sehingga satu slot hanya boleh dipegang satu host.

```text
id
live_schedule_id
host_id
is_active
created_at
updated_at
```

Relationship:

```text
LiveSchedule hasOne LiveHost
LiveHost belongsTo LiveSchedule
LiveHost belongsTo Host
```

## birthdays

```text
id
employee_name
division
birth_date
image
is_active
created_at
updated_at
```

---

# 11. API Architecture

React tidak boleh mengakses database secara langsung.

Architecture:

```text
React
  ↓
Laravel API
  ↓
Controller
  ↓
Eloquent Model
  ↓
MySQL
```

## Main display endpoint

Gunakan satu endpoint agregasi:

```text
GET /api/display
```

Expected response structure:

```json
{
  "success": true,
  "data": {
    "promotions": [],
    "achievements": [],
    "live_hosts": [],
    "birthdays": []
  }
}
```

Endpoint harus mengembalikan hanya data yang relevan untuk display.

Contoh:

- Promotion hanya aktif dan sedang dalam periode
- Achievement hanya aktif
- Live host hanya jadwal aktif; jadwal berulang tanpa filter tanggal
- Birthday hanya karyawan yang ulang tahun hari ini dan aktif

---

# 12. Data Refresh

Ada dua jenis timer yang berbeda.

## Slide timer

Setiap:

```text
5 detik
```

hanya mengganti slide.

## Data refresh

React melakukan polling:

```text
30–60 detik
```

untuk mengambil data terbaru.

Jangan melakukan request API setiap 5 detik hanya untuk mengganti slide.

Pastikan interval dibersihkan saat component unmount.

---

# 13. Digital Board UX

Halaman `/` harus benar-benar terasa seperti digital signage.

Tidak boleh ada:

- Navbar website biasa
- Sidebar
- Footer
- Scrollbar
- Login requirement
- Form
- Menu yang tidak diperlukan

Gunakan:

```css
width: 100vw;
height: 100vh;
overflow: hidden;
```

Pastikan:

- Tidak horizontal overflow
- Tidak vertical overflow
- Tidak ada content terpotong
- Tidak ada layout keluar viewport

---

# 14. Fullscreen

Sediakan kontrol fullscreen yang tidak mengganggu display.

Gunakan browser Fullscreen API jika memungkinkan.

Ketika fullscreen aktif:

- Kontrol disembunyikan
- Display memenuhi seluruh layar

Display harus tetap dapat berjalan tanpa fullscreen browser, tetapi fullscreen menjadi fitur yang disediakan.

---

# 15. Responsive Requirements

Target utama:

```text
1920 × 1080
1366 × 768
1280 × 720
3840 × 2160
```

Target device:

- TV
- Smart TV browser
- Desktop monitor
- Laptop
- PC signage

Gunakan responsive CSS:

- CSS Grid
- Flexbox
- `clamp()`
- `vw`
- `vh`
- responsive breakpoints

Jangan membuat layout fixed yang hanya cocok pada satu resolusi.

---

# 16. Visual Direction

Digital Board:

> Modern corporate digital signage.

Prioritas:

```text
READABILITY > DECORATION
```

Gunakan:

- Typography besar
- High contrast
- Strong visual hierarchy
- Spacing yang cukup
- Card yang jelas
- Animasi smooth
- Background modern
- Sedikit teks kecil
- Elemen yang mudah terbaca dari beberapa meter

Animasi yang disarankan:

- fade
- slide
- subtle scale

Hindari animasi berlebihan.

Admin dashboard boleh menggunakan visual dashboard yang lebih standar.

---

# 17. Admin Dashboard

Menu:

```text
Dashboard

CONTENT
├── Info Promosi
├── Achievement
├── Jadwal Host Live
└── Birthday

DISPLAY
└── Preview Display

ACCOUNT
└── Logout
```

Dashboard summary:

```text
Promosi Aktif
Achievement Aktif
Host Terjadwal
Birthday Hari Ini
```

---

# 18. Admin CRUD

Semua content harus memiliki CRUD.

## Promotion

- Create
- Read
- Update
- Delete
- Activate/deactivate
- Upload image
- Sort order

## Achievement

- Create
- Read
- Update
- Delete
- Activate/deactivate
- Upload image
- Sort order

## Live Host

- Create
- Read
- Update
- Delete
- Filter date
- Filter channel
- Activate/deactivate
- Sort order

## Birthday

- Create
- Read
- Update
- Delete
- Upload image
- Activate/deactivate

---

# 19. Image Storage

Gunakan Laravel Storage.

Directories:

```text
storage/app/public/promotions
storage/app/public/achievements
storage/app/public/birthdays
```

Pastikan:

```bash
php artisan storage:link
```

Allowed types:

```text
jpg
jpeg
png
webp
```

Recommended max upload:

```text
5 MB
```

Validasi server-side wajib dilakukan.

---

# 20. React Structure

Minimal structure:

```text
resources/js/
├── components/
├── layouts/
├── pages/
│   ├── display/
│   │   └── DigitalBoard.jsx
│   └── admin/
│       ├── Login.jsx
│       ├── Dashboard.jsx
│       ├── Promotions/
│       │   ├── Index.jsx
│       │   ├── Create.jsx
│       │   └── Edit.jsx
│       ├── Achievements/
│       │   ├── Index.jsx
│       │   ├── Create.jsx
│       │   └── Edit.jsx
│       ├── LiveHosts/
│       │   ├── Index.jsx
│       │   ├── Create.jsx
│       │   └── Edit.jsx
│       └── Birthdays/
│           ├── Index.jsx
│           ├── Create.jsx
│           └── Edit.jsx
├── services/
└── app.jsx
```

Agent boleh menyesuaikan struktur jika project architecture Laravel/React yang digunakan membutuhkan pendekatan berbeda. Jangan membuat struktur duplikatif hanya demi mengikuti contoh ini.

---

# 21. Error Handling

Jika API gagal:

Jangan menampilkan layar putih/blank.

Gunakan fallback:

```text
Informasi sedang diperbarui...
```

Lalu retry otomatis.

Jika image gagal:

- tampilkan fallback/placeholder
- jangan merusak layout

Jika tidak ada data:

- skip kategori tersebut
- jika seluruh kategori kosong, tampilkan empty state yang tetap profesional

---

# 22. Performance & Long-Running Display

TV dapat menjalankan halaman selama berjam-jam.

Pastikan:

- Tidak ada memory leak
- React timers dibersihkan
- Polling tidak menumpuk
- Tidak ada infinite re-render
- Image tidak di-download ulang secara tidak perlu
- API request tidak berlebihan
- Animasi menggunakan performa browser yang baik
- Data lama diganti dengan benar
- Display tetap berjalan setelah berjam-jam

Jika memungkinkan, gunakan image preloading/caching dengan bijak.

---

# 23. Authentication Requirements

Admin login:

Login menggunakan username dan password.

```text
/admin/login
```

Setelah login:

```text
/admin
```

Gunakan:

- Laravel authentication
- Password hashing
- Auth middleware
- Admin role middleware

Unauthorized access:

```text
/admin/*
/api/admin/*
```

harus ditolak.

Public `/` tidak membutuhkan authentication.

---

# 24. Seeders

Seeder wajib tersedia.

Default admin:

```text
username: admin
email: admin@example.com
password: admin
role: admin
```

Password wajib di-hash menggunakan Laravel.

Seed 8 live channels:

```text
Johen PUBG
Johen MLBB
Johen Roblox
Johen Valorant
Johen Free Fire
Johen E-Football
Johen FC Mobile
Monkey PUBG
```

Buat dummy data untuk:

- Promotions
- Achievements
- Live hosts
- Birthdays

Tujuannya agar aplikasi dapat langsung diuji setelah migration + seeding.

---

# 25. Environment

`.env.example` wajib disediakan.

Contoh:

```env
APP_NAME="Digital Information Board"

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=digital_information_board
DB_USERNAME=root
DB_PASSWORD=
```

Jangan menambahkan fallback SQLite.

---

# 26. Development Phases

## Phase 1 — Foundation

- Laravel setup
- React + Vite setup
- MySQL configuration
- Authentication
- Admin middleware
- API structure
- Base routing

## Phase 2 — Database

- Migrations
- Models
- Relationships
- Seeders
- Dummy data

## Phase 3 — Admin CMS

- Admin login
- Dashboard
- Promotion CRUD
- Achievement CRUD
- Live Host CRUD
- Birthday CRUD
- Image upload

## Phase 4 — Digital Display

- `/` display
- Display API
- Promotion slide
- Achievement slide
- Live Host slide
- Birthday slide
- 5-second rotation
- Empty category handling
- API polling
- Fullscreen

## Phase 5 — Polish

- TV responsive
- Desktop responsive
- Animations
- Image optimization
- Error handling
- Long-running testing
- UI polish

---

# 27. Acceptance Criteria

## Authentication

- [ ] Admin dapat login
- [ ] Admin dapat logout
- [ ] `/admin` tidak dapat diakses tanpa login
- [ ] Admin middleware bekerja
- [ ] Public tidak perlu login
- [ ] `/` langsung membuka display

## Promotions

- [ ] CRUD bekerja
- [ ] Upload image bekerja
- [ ] Active/inactive bekerja
- [ ] Date range bekerja
- [ ] Sort order bekerja
- [ ] Display hanya menampilkan promotion yang valid

## Achievements

- [ ] CRUD bekerja
- [ ] Upload image bekerja
- [ ] Active/inactive bekerja
- [ ] Display menampilkan achievement valid

## Live Host

- [ ] 8 default channels tersedia
- [ ] CRUD jadwal bekerja
- [ ] Date filtering bekerja
- [ ] Channel filtering bekerja
- [ ] Host dapat ditambahkan
- [ ] Display hanya menampilkan jadwal aktif

## Birthday

- [ ] CRUD bekerja
- [ ] Birth date tersimpan
- [ ] Birthday hari ini terdeteksi otomatis
- [ ] Multiple birthday didukung
- [ ] Display hanya menampilkan birthday hari ini

## Display

- [ ] Full viewport
- [ ] No scrollbar
- [ ] No login
- [ ] Auto start
- [ ] Slide berubah setiap 5 detik
- [ ] Slide loop terus menerus
- [ ] Empty category dilewati
- [ ] Data refresh 30–60 detik
- [ ] API error tidak membuat blank screen
- [ ] Responsive TV
- [ ] Responsive desktop
- [ ] Fullscreen tersedia

## Database

- [ ] MySQL digunakan
- [ ] SQLite tidak digunakan
- [ ] Migrations tersedia
- [ ] Seeders tersedia
- [ ] `.env.example` menggunakan MySQL

---

# 28. Important Agent Rules

1. Baca `PRD.md` sebelum melakukan implementasi besar.
2. Jangan mengubah requirement utama tanpa alasan teknis yang jelas.
3. Jika menemukan requirement yang ambigu, pilih solusi yang paling sederhana dan maintainable.
4. Jangan menggunakan SQLite.
5. Jangan membuat public display membutuhkan login.
6. Jangan menggabungkan admin dashboard dengan public display menjadi satu UX.
7. Jangan meng-hardcode data bisnis yang seharusnya berasal dari database.
8. Jangan menghapus functionality yang sudah bekerja tanpa alasan.
9. Setelah setiap phase selesai, jalankan test/build yang relevan.
10. Update `TASKS.md` setelah menyelesaikan task.
11. Jika terjadi perubahan arsitektur penting, dokumentasikan di `README.md`.
12. Prioritaskan readability dan reliability pada public display.
13. Pastikan display dapat berjalan lama tanpa memory leak.
14. Jangan melakukan API polling setiap 5 detik hanya karena slide berpindah setiap 5 detik.
15. Sebelum menganggap fitur selesai, verifikasi acceptance criteria terkait.

---

# 29. Definition of Done

Aplikasi dianggap selesai apabila:

```text
Admin
  ↓
Login
  ↓
Manage Content
  ↓
MySQL
  ↓
Laravel API
  ↓
React Display
  ↓
TV
```

dan public display dapat:

- dibuka langsung melalui `/`
- berjalan tanpa login
- memenuhi seluruh viewport
- berganti informasi otomatis setiap 5 detik
- mengambil data terbaru secara otomatis
- menampilkan informasi hari ini
- menangani kategori kosong
- menangani API/image error
- berjalan stabil dalam waktu lama
- digunakan pada resolusi TV yang umum

