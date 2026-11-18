# Sistem Pencarian Ruang Kelas Universitas Nusa Putra

Aplikasi web sederhana (PHP + MySQL) untuk mencari ruang kelas yang kosong secara langsung.
Tanpa framework dan tanpa composer — cukup salin folder ini ke server.

## Fitur

**Halaman publik (`index.php`)**
- Mode **Sekarang**: ruang yang kosong mulai detik ini selama 15 menit – 3 jam ke depan.
- Mode **Pilih waktu**: cek ketersediaan pada tanggal dan rentang jam tertentu.
- Filter: kata kunci (kode / nama / fasilitas), gedung, kapasitas minimal, dan status (kosong / terisi / semua).
- Tiap kartu menampilkan "kosong sampai jam berapa", atau kegiatan yang sedang memakai ruang dan kapan ruang bebas lagi.
- **Real time**: data diperbarui otomatis tiap 30 detik (bisa dimatikan), saat tab kembali aktif, dan setiap filter diubah. Jam di pojok kanan atas mengikuti jam server.
- Tampilan responsif (HP sampai desktop) dan mendukung mode gelap.

**Panel admin (`admin/`)**
- Kelola **ruang** (tambah, ubah, nonaktifkan, hapus).
- Kelola **jadwal kuliah mingguan** (berulang tiap minggu).
- Kelola **peminjaman** sekali pakai (rapat, ujian, seminar).
- Deteksi otomatis jadwal/pinjaman yang bentrok.

## Cara memasang

1. Pasang XAMPP, Laragon, atau stack PHP 7.4+ dengan MySQL/MariaDB.
2. Salin folder `ruang-kosong` ke folder web (`htdocs` untuk XAMPP, `www` untuk Laragon).
3. Impor `database.sql` — lewat phpMyAdmin (tab **Import**) atau perintah:
   `mysql -u root -p < database.sql`
   (otomatis membuat database `ruang_kosong` beserta data contoh)
4. Buka `includes/config.php` dan sesuaikan user/password database serta zona waktu (`APP_TIMEZONE`).
5. Akses `http://localhost/ruang-kosong/`.
http://localhost/ruang-kosong/admin/login.php`.
**Login admin awal:** `admin` / `admin123` di `
**Segera ganti password ini** sebelum dipakai di server sungguhan (lihat bagian di bawah).

## Cara kerja

Ruang dianggap **terisi** pada rentang waktu `[mulai, selesai)` bila ada jadwal kuliah pada hari yang sama
atau peminjaman pada tanggal yang sama yang waktunya beririsan
(`jam_mulai < selesai` dan `jam_selesai > mulai`). Kuliah yang berakhir tepat pukul 11:00 tidak
membuat ruang dianggap terisi pada 11:00. Logika ini ada di `includes/functions.php`.

## Struktur folder

```
ruang-kosong/
├── index.php              Halaman pencarian publik
├── database.sql           Skema + data contoh
├── api/
│   └── search.php         Endpoint JSON (dipanggil oleh app.js)
├── assets/
│   ├── style.css
│   └── app.js             Pencarian langsung, auto-refresh, jam server
├── admin/
│   ├── login.php  logout.php  index.php
│   └── ruang.php  jadwal.php  peminjaman.php
└── includes/
    ├── config.php         Pengaturan database & zona waktu
    ├── db.php             Koneksi PDO
    ├── bootstrap.php      Pemuat bersama
    ├── functions.php      Logika inti pencarian
    ├── auth.php           Sesi admin, CSRF, pesan flash
    └── admin_layout.php   Kerangka halaman admin
```

## Mengganti password admin

Buat hash baru:

```
php -r "echo password_hash('PASSWORD_BARU', PASSWORD_DEFAULT);"
```

lalu jalankan di MySQL:

```sql
UPDATE admin SET password_hash = '<hash di atas>' WHERE username = 'admin';
```

## Catatan keamanan

- Semua query memakai prepared statement; keluaran HTML di-escape; form admin dilindungi token CSRF.
- Folder `includes/` sudah dilengkapi `.htaccess` agar tidak bisa diakses langsung di Apache.
  Jika memakai Nginx, blokir akses ke `/includes/` lewat konfigurasi server.
- Di server sungguhan: gunakan HTTPS, jangan pakai user `root` MySQL (buat user khusus), dan ganti password admin.

## Pengembangan lebih lanjut

Beberapa ide: masa berlaku jadwal per semester, hari libur, impor jadwal dari CSV,
beberapa akun admin dengan pegangan, atau tombol "ajukan peminjaman" untuk penggunaan umum.
