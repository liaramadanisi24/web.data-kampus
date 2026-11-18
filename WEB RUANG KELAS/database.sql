-- ============================================================
--  Sistem Pencarian Ruang Kelas Universitas Nusa Putra — skema database + data contoh
--  Impor lewat phpMyAdmin (tab Import) atau:
--     mysql -u root -p < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS ruang_kosong
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ruang_kosong;

DROP TABLE IF EXISTS peminjaman;
DROP TABLE IF EXISTS jadwal;
DROP TABLE IF EXISTS ruang;
DROP TABLE IF EXISTS admin;

-- Akun admin
CREATE TABLE admin (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- Daftar ruang kelas
CREATE TABLE ruang (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kode      VARCHAR(20)  NOT NULL UNIQUE,
  nama      VARCHAR(100) NOT NULL,
  gedung    VARCHAR(50)  NOT NULL,
  lantai    TINYINT UNSIGNED NOT NULL DEFAULT 1,
  kapasitas SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  fasilitas VARCHAR(255) NOT NULL DEFAULT '',   -- dipisah koma
  aktif     TINYINT(1)   NOT NULL DEFAULT 1,
  INDEX idx_ruang_gedung (gedung, aktif)
) ENGINE=InnoDB;

-- Jadwal kuliah mingguan (berulang tiap minggu)
-- hari: 1 = Senin ... 7 = Minggu (ISO-8601)
CREATE TABLE jadwal (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ruang_id    INT UNSIGNED NOT NULL,
  hari        TINYINT UNSIGNED NOT NULL,
  jam_mulai   TIME NOT NULL,
  jam_selesai TIME NOT NULL,
  mata_kuliah VARCHAR(120) NOT NULL,
  dosen       VARCHAR(100) NOT NULL DEFAULT '',
  CONSTRAINT fk_jadwal_ruang FOREIGN KEY (ruang_id) REFERENCES ruang(id) ON DELETE CASCADE,
  INDEX idx_jadwal_cari (hari, ruang_id, jam_mulai, jam_selesai)
) ENGINE=InnoDB;

-- Peminjaman sekali pakai (rapat, ujian, seminar, dll.)
CREATE TABLE peminjaman (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ruang_id    INT UNSIGNED NOT NULL,
  tanggal     DATE NOT NULL,
  jam_mulai   TIME NOT NULL,
  jam_selesai TIME NOT NULL,
  keperluan   VARCHAR(150) NOT NULL,
  peminjam    VARCHAR(100) NOT NULL DEFAULT '',
  CONSTRAINT fk_pinjam_ruang FOREIGN KEY (ruang_id) REFERENCES ruang(id) ON DELETE CASCADE,
  INDEX idx_pinjam_cari (tanggal, ruang_id, jam_mulai, jam_selesai)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  Data contoh
-- ------------------------------------------------------------

-- Login awal:  username = admin   password = admin123   (SEGERA GANTI!)
INSERT INTO admin (username, password_hash) VALUES
('admin', '$2y$10$k7UMGDFNftt.C1lSxXWBAOERAWsZg.XrM4f.ia7zc65H/WiIRux3q');

INSERT INTO ruang (kode, nama, gedung, lantai, kapasitas, fasilitas) VALUES
('A-101', 'Ruang Kuliah A101', 'Gedung A', 1, 40, 'Proyektor, AC, Papan tulis'),
('A-102', 'Ruang Kuliah A102', 'Gedung A', 1, 40, 'Proyektor, AC, Papan tulis'),
('A-201', 'Ruang Kuliah A201', 'Gedung A', 2, 60, 'Proyektor, AC, Sound system'),
('A-202', 'Ruang Kuliah A202', 'Gedung A', 2, 60, 'Proyektor, AC, Sound system'),
('B-101', 'Ruang Kuliah B101', 'Gedung B', 1, 30, 'Proyektor, Kipas angin'),
('B-102', 'Ruang Kuliah B102', 'Gedung B', 1, 30, 'Proyektor, Kipas angin'),
('B-201', 'Ruang Seminar B201', 'Gedung B', 2, 100, 'Proyektor, AC, Sound system, Panggung'),
('B-202', 'Ruang Diskusi B202', 'Gedung B', 2, 20, 'AC, Papan tulis, Meja bundar'),
('C-101', 'Laboratorium Komputer 1', 'Gedung C', 1, 35, 'Komputer, AC, Proyektor, Wi-Fi'),
('C-102', 'Laboratorium Komputer 2', 'Gedung C', 1, 35, 'Komputer, AC, Proyektor, Wi-Fi'),
('C-201', 'Laboratorium Bahasa', 'Gedung C', 2, 30, 'Headset, AC, Proyektor'),
('C-202', 'Ruang Kuliah C202', 'Gedung C', 2, 45, 'Proyektor, AC, Papan tulis');

INSERT INTO jadwal (ruang_id, hari, jam_mulai, jam_selesai, mata_kuliah, dosen) VALUES
(1, 1, '09:20:00', '11:00:00', 'Algoritma dan Pemrograman', 'Dr. Rahmat Hidayat'),
(1, 1, '14:50:00', '16:30:00', 'Basis Data', 'Prof. Anita Lestari'),
(1, 2, '09:20:00', '11:00:00', 'Kalkulus I', 'Agus Setiawan, M.Sc.'),
(1, 2, '14:50:00', '16:30:00', 'Fisika Dasar', 'Ir. Budi Santoso, M.T.'),
(1, 3, '09:20:00', '11:00:00', 'Pengantar Akuntansi', 'Dewi Kartika, M.Pd.'),
(1, 3, '14:50:00', '16:30:00', 'Statistika', 'Dra. Sari Wulandari, M.Si.'),
(1, 4, '09:20:00', '11:00:00', 'Bahasa Inggris', 'Hendra Pratama, M.Kom.'),
(1, 4, '14:50:00', '16:30:00', 'Pancasila', 'Dr. Rahmat Hidayat'),
(1, 5, '09:20:00', '11:00:00', 'Jaringan Komputer', 'Prof. Anita Lestari'),
(1, 5, '14:50:00', '16:30:00', 'Manajemen', 'Agus Setiawan, M.Sc.'),
(2, 1, '07:30:00', '09:10:00', 'Ekonomi Mikro', 'Ir. Budi Santoso, M.T.'),
(2, 1, '11:10:00', '12:50:00', 'Struktur Data', 'Dewi Kartika, M.Pd.'),
(2, 2, '07:30:00', '09:10:00', 'Sistem Operasi', 'Dra. Sari Wulandari, M.Si.'),
(2, 2, '11:10:00', '12:50:00', 'Aljabar Linear', 'Hendra Pratama, M.Kom.'),
(2, 3, '07:30:00', '09:10:00', 'Kewirausahaan', 'Dr. Rahmat Hidayat'),
(2, 3, '11:10:00', '12:50:00', 'Pemrograman Web', 'Prof. Anita Lestari'),
(2, 4, '07:30:00', '09:10:00', 'Algoritma dan Pemrograman', 'Agus Setiawan, M.Sc.'),
(2, 4, '11:10:00', '12:50:00', 'Basis Data', 'Ir. Budi Santoso, M.T.'),
(2, 5, '07:30:00', '09:10:00', 'Kalkulus I', 'Dewi Kartika, M.Pd.'),
(2, 5, '11:10:00', '12:50:00', 'Fisika Dasar', 'Dra. Sari Wulandari, M.Si.'),
(3, 1, '09:20:00', '11:00:00', 'Pengantar Akuntansi', 'Hendra Pratama, M.Kom.'),
(3, 1, '13:00:00', '14:40:00', 'Statistika', 'Dr. Rahmat Hidayat'),
(3, 2, '09:20:00', '11:00:00', 'Bahasa Inggris', 'Prof. Anita Lestari'),
(3, 2, '13:00:00', '14:40:00', 'Pancasila', 'Agus Setiawan, M.Sc.'),
(3, 3, '09:20:00', '11:00:00', 'Jaringan Komputer', 'Ir. Budi Santoso, M.T.'),
(3, 3, '13:00:00', '14:40:00', 'Manajemen', 'Dewi Kartika, M.Pd.'),
(3, 4, '09:20:00', '11:00:00', 'Ekonomi Mikro', 'Dra. Sari Wulandari, M.Si.'),
(3, 4, '13:00:00', '14:40:00', 'Struktur Data', 'Hendra Pratama, M.Kom.'),
(3, 5, '09:20:00', '11:00:00', 'Sistem Operasi', 'Dr. Rahmat Hidayat'),
(3, 5, '13:00:00', '14:40:00', 'Aljabar Linear', 'Prof. Anita Lestari'),
(4, 1, '11:10:00', '12:50:00', 'Kewirausahaan', 'Agus Setiawan, M.Sc.'),
(4, 1, '14:50:00', '16:30:00', 'Pemrograman Web', 'Ir. Budi Santoso, M.T.'),
(4, 2, '11:10:00', '12:50:00', 'Algoritma dan Pemrograman', 'Dewi Kartika, M.Pd.'),
(4, 2, '14:50:00', '16:30:00', 'Basis Data', 'Dra. Sari Wulandari, M.Si.'),
(4, 3, '11:10:00', '12:50:00', 'Kalkulus I', 'Hendra Pratama, M.Kom.'),
(4, 3, '14:50:00', '16:30:00', 'Fisika Dasar', 'Dr. Rahmat Hidayat'),
(4, 4, '11:10:00', '12:50:00', 'Pengantar Akuntansi', 'Prof. Anita Lestari'),
(4, 4, '14:50:00', '16:30:00', 'Statistika', 'Agus Setiawan, M.Sc.'),
(4, 5, '11:10:00', '12:50:00', 'Bahasa Inggris', 'Ir. Budi Santoso, M.T.'),
(4, 5, '14:50:00', '16:30:00', 'Pancasila', 'Dewi Kartika, M.Pd.'),
(5, 1, '07:30:00', '09:10:00', 'Jaringan Komputer', 'Dra. Sari Wulandari, M.Si.'),
(5, 1, '13:00:00', '14:40:00', 'Manajemen', 'Hendra Pratama, M.Kom.'),
(5, 2, '07:30:00', '09:10:00', 'Ekonomi Mikro', 'Dr. Rahmat Hidayat'),
(5, 2, '13:00:00', '14:40:00', 'Struktur Data', 'Prof. Anita Lestari'),
(5, 3, '07:30:00', '09:10:00', 'Sistem Operasi', 'Agus Setiawan, M.Sc.'),
(5, 3, '13:00:00', '14:40:00', 'Aljabar Linear', 'Ir. Budi Santoso, M.T.'),
(5, 4, '07:30:00', '09:10:00', 'Kewirausahaan', 'Dewi Kartika, M.Pd.'),
(5, 4, '13:00:00', '14:40:00', 'Pemrograman Web', 'Dra. Sari Wulandari, M.Si.'),
(5, 5, '07:30:00', '09:10:00', 'Algoritma dan Pemrograman', 'Hendra Pratama, M.Kom.'),
(5, 5, '13:00:00', '14:40:00', 'Basis Data', 'Dr. Rahmat Hidayat'),
(6, 1, '09:20:00', '11:00:00', 'Kalkulus I', 'Prof. Anita Lestari'),
(6, 1, '14:50:00', '16:30:00', 'Fisika Dasar', 'Agus Setiawan, M.Sc.'),
(6, 2, '09:20:00', '11:00:00', 'Pengantar Akuntansi', 'Ir. Budi Santoso, M.T.'),
(6, 2, '14:50:00', '16:30:00', 'Statistika', 'Dewi Kartika, M.Pd.'),
(6, 3, '09:20:00', '11:00:00', 'Bahasa Inggris', 'Dra. Sari Wulandari, M.Si.'),
(6, 3, '14:50:00', '16:30:00', 'Pancasila', 'Hendra Pratama, M.Kom.'),
(6, 4, '09:20:00', '11:00:00', 'Jaringan Komputer', 'Dr. Rahmat Hidayat'),
(6, 4, '14:50:00', '16:30:00', 'Manajemen', 'Prof. Anita Lestari'),
(6, 5, '09:20:00', '11:00:00', 'Ekonomi Mikro', 'Agus Setiawan, M.Sc.'),
(6, 5, '14:50:00', '16:30:00', 'Struktur Data', 'Ir. Budi Santoso, M.T.'),
(7, 1, '07:30:00', '09:10:00', 'Sistem Operasi', 'Dewi Kartika, M.Pd.'),
(7, 1, '11:10:00', '12:50:00', 'Aljabar Linear', 'Dra. Sari Wulandari, M.Si.'),
(7, 2, '07:30:00', '09:10:00', 'Kewirausahaan', 'Hendra Pratama, M.Kom.'),
(7, 2, '11:10:00', '12:50:00', 'Pemrograman Web', 'Dr. Rahmat Hidayat'),
(7, 3, '07:30:00', '09:10:00', 'Algoritma dan Pemrograman', 'Prof. Anita Lestari'),
(7, 3, '11:10:00', '12:50:00', 'Basis Data', 'Agus Setiawan, M.Sc.'),
(7, 4, '07:30:00', '09:10:00', 'Kalkulus I', 'Ir. Budi Santoso, M.T.'),
(7, 4, '11:10:00', '12:50:00', 'Fisika Dasar', 'Dewi Kartika, M.Pd.'),
(7, 5, '07:30:00', '09:10:00', 'Pengantar Akuntansi', 'Dra. Sari Wulandari, M.Si.'),
(7, 5, '11:10:00', '12:50:00', 'Statistika', 'Hendra Pratama, M.Kom.'),
(8, 1, '09:20:00', '11:00:00', 'Bahasa Inggris', 'Dr. Rahmat Hidayat'),
(8, 1, '13:00:00', '14:40:00', 'Pancasila', 'Prof. Anita Lestari'),
(8, 2, '09:20:00', '11:00:00', 'Jaringan Komputer', 'Agus Setiawan, M.Sc.'),
(8, 2, '13:00:00', '14:40:00', 'Manajemen', 'Ir. Budi Santoso, M.T.'),
(8, 3, '09:20:00', '11:00:00', 'Ekonomi Mikro', 'Dewi Kartika, M.Pd.'),
(8, 3, '13:00:00', '14:40:00', 'Struktur Data', 'Dra. Sari Wulandari, M.Si.'),
(8, 4, '09:20:00', '11:00:00', 'Sistem Operasi', 'Hendra Pratama, M.Kom.'),
(8, 4, '13:00:00', '14:40:00', 'Aljabar Linear', 'Dr. Rahmat Hidayat'),
(8, 5, '09:20:00', '11:00:00', 'Kewirausahaan', 'Prof. Anita Lestari'),
(8, 5, '13:00:00', '14:40:00', 'Pemrograman Web', 'Agus Setiawan, M.Sc.'),
(9, 1, '11:10:00', '12:50:00', 'Algoritma dan Pemrograman', 'Ir. Budi Santoso, M.T.'),
(9, 1, '14:50:00', '16:30:00', 'Basis Data', 'Dewi Kartika, M.Pd.'),
(9, 2, '11:10:00', '12:50:00', 'Kalkulus I', 'Dra. Sari Wulandari, M.Si.'),
(9, 2, '14:50:00', '16:30:00', 'Fisika Dasar', 'Hendra Pratama, M.Kom.'),
(9, 3, '11:10:00', '12:50:00', 'Pengantar Akuntansi', 'Dr. Rahmat Hidayat'),
(9, 3, '14:50:00', '16:30:00', 'Statistika', 'Prof. Anita Lestari'),
(9, 4, '11:10:00', '12:50:00', 'Bahasa Inggris', 'Agus Setiawan, M.Sc.'),
(9, 4, '14:50:00', '16:30:00', 'Pancasila', 'Ir. Budi Santoso, M.T.'),
(9, 5, '11:10:00', '12:50:00', 'Jaringan Komputer', 'Dewi Kartika, M.Pd.'),
(9, 5, '14:50:00', '16:30:00', 'Manajemen', 'Dra. Sari Wulandari, M.Si.'),
(10, 1, '07:30:00', '09:10:00', 'Ekonomi Mikro', 'Hendra Pratama, M.Kom.'),
(10, 1, '13:00:00', '14:40:00', 'Struktur Data', 'Dr. Rahmat Hidayat'),
(10, 2, '07:30:00', '09:10:00', 'Sistem Operasi', 'Prof. Anita Lestari'),
(10, 2, '13:00:00', '14:40:00', 'Aljabar Linear', 'Agus Setiawan, M.Sc.'),
(10, 3, '07:30:00', '09:10:00', 'Kewirausahaan', 'Ir. Budi Santoso, M.T.'),
(10, 3, '13:00:00', '14:40:00', 'Pemrograman Web', 'Dewi Kartika, M.Pd.'),
(10, 4, '07:30:00', '09:10:00', 'Algoritma dan Pemrograman', 'Dra. Sari Wulandari, M.Si.'),
(10, 4, '13:00:00', '14:40:00', 'Basis Data', 'Hendra Pratama, M.Kom.'),
(10, 5, '07:30:00', '09:10:00', 'Kalkulus I', 'Dr. Rahmat Hidayat'),
(10, 5, '13:00:00', '14:40:00', 'Fisika Dasar', 'Prof. Anita Lestari'),
(11, 1, '09:20:00', '11:00:00', 'Pengantar Akuntansi', 'Agus Setiawan, M.Sc.'),
(11, 1, '14:50:00', '16:30:00', 'Statistika', 'Ir. Budi Santoso, M.T.'),
(11, 2, '09:20:00', '11:00:00', 'Bahasa Inggris', 'Dewi Kartika, M.Pd.'),
(11, 2, '14:50:00', '16:30:00', 'Pancasila', 'Dra. Sari Wulandari, M.Si.'),
(11, 3, '09:20:00', '11:00:00', 'Jaringan Komputer', 'Hendra Pratama, M.Kom.'),
(11, 3, '14:50:00', '16:30:00', 'Manajemen', 'Dr. Rahmat Hidayat'),
(11, 4, '09:20:00', '11:00:00', 'Ekonomi Mikro', 'Prof. Anita Lestari'),
(11, 4, '14:50:00', '16:30:00', 'Struktur Data', 'Agus Setiawan, M.Sc.'),
(11, 5, '09:20:00', '11:00:00', 'Sistem Operasi', 'Ir. Budi Santoso, M.T.'),
(11, 5, '14:50:00', '16:30:00', 'Aljabar Linear', 'Dewi Kartika, M.Pd.'),
(12, 1, '07:30:00', '09:10:00', 'Kewirausahaan', 'Dra. Sari Wulandari, M.Si.'),
(12, 1, '11:10:00', '12:50:00', 'Pemrograman Web', 'Hendra Pratama, M.Kom.'),
(12, 2, '07:30:00', '09:10:00', 'Algoritma dan Pemrograman', 'Dr. Rahmat Hidayat'),
(12, 2, '11:10:00', '12:50:00', 'Basis Data', 'Prof. Anita Lestari'),
(12, 3, '07:30:00', '09:10:00', 'Kalkulus I', 'Agus Setiawan, M.Sc.'),
(12, 3, '11:10:00', '12:50:00', 'Fisika Dasar', 'Ir. Budi Santoso, M.T.'),
(12, 4, '07:30:00', '09:10:00', 'Pengantar Akuntansi', 'Dewi Kartika, M.Pd.'),
(12, 4, '11:10:00', '12:50:00', 'Statistika', 'Dra. Sari Wulandari, M.Si.'),
(12, 5, '07:30:00', '09:10:00', 'Bahasa Inggris', 'Hendra Pratama, M.Kom.'),
(12, 5, '11:10:00', '12:50:00', 'Pancasila', 'Dr. Rahmat Hidayat'),
(1, 6, '07:30:00', '09:10:00', 'Basis Data', 'Dra. Sari Wulandari, M.Si.'),
(3, 6, '09:20:00', '11:00:00', 'Pengantar Akuntansi', 'Prof. Anita Lestari'),
(9, 6, '07:30:00', '09:10:00', 'Manajemen', 'Ir. Budi Santoso, M.T.'),
(10, 6, '11:10:00', '12:50:00', 'Sistem Operasi', 'Prof. Anita Lestari'),
(12, 6, '09:20:00', '11:00:00', 'Aljabar Linear', 'Dewi Kartika, M.Pd.');

-- Peminjaman contoh (relatif terhadap tanggal impor, agar demo selalu terlihat)
INSERT INTO peminjaman (ruang_id, tanggal, jam_mulai, jam_selesai, keperluan, peminjam) VALUES
(7, CURDATE(), '09:00:00', '12:00:00', 'Seminar Nasional Teknologi', 'Himpunan Mahasiswa Informatika'),
(8, CURDATE(), '13:00:00', '15:00:00', 'Rapat Dosen Program Studi', 'Kaprodi Manajemen'),
(2, CURDATE(), '15:00:00', '17:00:00', 'Pelatihan Soft Skill', 'UKM Kewirausahaan'),
(7, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', '16:00:00', 'Ujian Sertifikasi', 'Pusat Karir'),
(4, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', '12:00:00', 'Sidang Skripsi', 'Fakultas Teknik');
