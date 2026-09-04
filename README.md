# Absensi Magang UP3 Serpong — Fullstack PHP + MySQL

Project ini dibuat agar mudah dijalankan di **macOS + XAMPP** tanpa framework tambahan:
- Frontend: HTML + CSS + JavaScript
- Backend: PHP 8+ (PDO)
- Database: MySQL/MariaDB
- Auth: PHP Session + `password_hash()`
- Geolocation: Browser Geolocation API + Haversine
- Bukti presensi: kamera browser (`getUserMedia`)
- Analisis: radar/spider chart SVG

## 1. Struktur folder

```text
absensi-pln/
├── api.php
├── config.php
├── database/
│   └── schema.sql
├── docs/
│   └── flowchart-revised.md
├── public/
│   ├── index.php
│   └── assets/
│       ├── app.css
│       ├── app.js
│       ├── Kantor-UP3-Serpong.png
│       └── logo-pln.jpg
└── storage/
    ├── logs/
    │   └── otp.log
    └── uploads/
```

## 2. Menjalankan di MacBook dengan XAMPP

### Step A — Install/start XAMPP

1. Install XAMPP for macOS.
2. Buka **XAMPP Manager**.
3. Start:
   - Apache
   - MySQL
4. Buka browser:
   - `http://localhost/`
   - phpMyAdmin: `http://localhost/phpmyadmin/`

### Step B — Masukkan project ke htdocs

Copy folder `absensi-pln` ke:

```text
/Applications/XAMPP/htdocs/
```

Sehingga menjadi:

```text
/Applications/XAMPP/htdocs/absensi-pln/
```

### Step C — Buat database

1. Buka `http://localhost/phpmyadmin/`.
2. Pilih tab **Import**.
3. Pilih:

```text
absensi-pln/database/schema.sql
```

4. Klik **Import/Go**.
5. Pastikan database `absensi_pln` muncul.

> Jika database lama masih kosong, paling aman backup lalu gunakan schema.sql revisi ini. Schema ini sudah memperbaiki urutan foreign key, tipe password, foto, lokasi presensi, relasi mentor-intern, dan data jadwal.

### Step D — Hubungkan PHP ke MySQL

Buka:

```text
absensi-pln/config.php
```

Bagian default:

```php
$dbHost = '127.0.0.1';
$dbName = 'absensi_pln';
$dbUser = 'root';
$dbPass = '';
```

Untuk XAMPP default biasanya sudah benar. Jika MySQL Anda memiliki password root, ubah `$dbPass`.

### Step E — Jalankan website

Buka:

```text
http://localhost/absensi-pln/public/
```

atau:

```text
http://localhost/absensi-pln/public/index.php?page=role
```

Jika halaman "Siapa nih?" muncul, koneksi PHP + MySQL sudah berjalan.

## 3. Cara memastikan database benar-benar tersambung

`config.php` membuat koneksi menggunakan PDO:

```php
$pdo = new PDO(
    "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPass
);
```

Semua endpoint di `api.php` menggunakan `$pdo` yang sama.

Jadi alurnya:

```text
Browser
   ↓
public/index.php
   ↓
JavaScript fetch()
   ↓
api.php
   ↓
PDO
   ↓
MySQL / database absensi_pln
```

## 4. Fitur yang sudah dibuat

### Intern
- Pilih role
- Login
- Register data diri
- Register username/password
- Live username availability check
- Password confirmation
- Lupa password + OTP mode lokal
- Dashboard kedatangan/kepulangan/perizinan
- Geofence radius kantor 100 m
- Presensi kedatangan dengan kamera
- Presensi kepulangan dengan kamera
- Deteksi terlambat sesuai jadwal
- Input alasan keterlambatan
- Perizinan rentang tanggal
- History
- Profile/data diri
- Edit profile dengan konfirmasi password
- Analisis spider/radar chart:
  - Rajin
  - Tepat waktu
  - Lembur
  - Konsistensi

### Mentor
- Login/register
- Dashboard
- Daftar intern berdasarkan mentor/divisi
- Sinkronisasi intern berdasarkan divisi
- Detail intern
- Hapus intern + akun
- Analisis intern
- Presensi intern
- Approval/decline perizinan
- Counter perizinan pending
- Sidebar profile

## 5. Pengujian kamera dan lokasi di MacBook

Untuk presensi, browser meminta akses kamera dan lokasi.

Di Safari:
1. Buka `http://localhost/absensi-pln/public/`.
2. Saat diminta, Allow Camera.
3. Allow Location.
4. Jika pernah menolak:
   Safari → Settings for This Website → Camera → Allow
   dan Location → Allow.

Kamera presensi menggunakan `navigator.mediaDevices.getUserMedia()`, bukan file picker, sehingga alur presensi memang diarahkan untuk mengambil foto dari kamera.

## 6. OTP

Karena XAMPP lokal belum otomatis memiliki layanan email/SMS, mode project ini membuat OTP dan menulisnya ke:

```text
storage/logs/otp.log
```

Selain itu karena `APP_ENV = 'local'`, OTP dikembalikan ke browser agar mudah dites.

Contoh:

```text
Kode lokal: 123456
```

### Untuk production

Jangan tampilkan OTP di browser.

Ubah:

```php
const APP_ENV = 'local';
```

menjadi:

```php
const APP_ENV = 'production';
```

Lalu hubungkan `send_otp` ke provider email/SMS yang benar. Untuk email production, PHPMailer + SMTP lebih disarankan daripada `mail()` mentah.

## 7. Logika radius

Lokasi kantor berasal dari:

```sql
PLN UP3 Serpong
latitude  = -6.290760829438223
longitude = 106.66449567579366
radius    = 100 meter
```

Sistem menghitung jarak menggunakan Haversine.

Penting: browser adalah sumber koordinat user, tetapi validasi final dilakukan kembali di server ketika presensi dikirim. Ini mencegah UI saja yang menentukan apakah user boleh presensi.

## 8. Logika jam kerja

Schema menyimpan:

```text
Senin   07:30 - 16:30
Selasa  07:30 - 16:30
Rabu    07:30 - 16:30
Kamis   07:30 - 16:30
Jumat   07:00 - 17:00
```

Sabtu/Minggu ditandai bukan hari kerja.

Untuk keterlambatan:
- Senin–Kamis > 07:30 = terlambat
- Jumat > 07:00 = terlambat

Untuk kepulangan:
- Senin–Kamis >= 16:30
- Jumat >= 17:00

## 9. Catatan penting tentang "Minggu"

Instruksi awal menyebut kepulangan `>17.00 hari Minggu`. Flowchart/database yang tersedia justru mendefinisikan jadwal kerja Senin–Jumat dan Jumat sampai 17:00. Karena itu project ini menggunakan **Jumat 17:00**, bukan Minggu 17:00.

Jika kantor memang bekerja pada Minggu, ubah data:

```sql
UPDATE jadwal_kerja
SET jam_masuk='07:00:00', jam_pulang='17:00:00'
WHERE hari='Minggu';
```

## 10. Catatan tentang tombol Kedatangan

Instruksi menyebut "sudah melakukan presensi hari itu", tetapi secara logika bisnis tombol Kedatangan harus tersedia ketika user **belum** melakukan presensi kedatangan hari itu.

Project ini menggunakan interpretasi:

```text
tidak ada izin
+ berada dalam radius 100 m
+ BELUM presensi kedatangan
= boleh klik Kedatangan
```

Jika sudah presensi, tombol disabled.

## 11. Analisis spider chart

Nilai dibuat 0–100:

### Rajin

```text
jumlah hari hadir / jumlah hari kerja pada periode magang × 100
```

### Tepat waktu

```text
jumlah datang tepat waktu / jumlah hari hadir × 100
```

### Lembur

```text
jumlah hari pulang setelah jam kerja / jumlah hari hadir × 100
```

### Konsistensi

Mengukur variasi durasi kerja harian. Semakin kecil variasi jam kerja, semakin tinggi nilai konsistensi.

## 12. Rekomendasi sebelum dipakai sungguhan

Untuk tugas/demo kampus, project ini sudah cukup untuk dikembangkan.

Untuk production:
- aktifkan HTTPS
- jangan tampilkan OTP di browser
- pindahkan credential DB ke environment variable
- validasi upload image MIME + ukuran lebih ketat
- tambahkan rate limit login/OTP
- tambahkan audit log
- tambahkan CSRF pada semua request
- validasi koordinat dan waktu server
- tambahkan role/permission yang lebih ketat
- backup database rutin
- jangan mengizinkan mentor menghapus intern tanpa konfirmasi/audit
