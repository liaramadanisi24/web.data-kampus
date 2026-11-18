<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();

$pdo = db();

$jmlRuang  = (int)$pdo->query('SELECT COUNT(*) FROM ruang WHERE aktif = 1')->fetchColumn();
$jmlJadwal = (int)$pdo->query('SELECT COUNT(*) FROM jadwal')->fetchColumn();

$st = $pdo->prepare('SELECT COUNT(*) FROM peminjaman WHERE tanggal = :t');
$st->execute([':t' => date('Y-m-d')]);
$jmlPinjamHariIni = (int)$st->fetchColumn();

[$tanggal, $mulai, $selesai] = jendela_sekarang(60);
$semua = cari_ruang($pdo, $tanggal, $mulai, $selesai);
$kosongSekarang = count(array_filter($semua, fn($r) => $r['status'] === 'kosong'));

admin_header('Dasbor', 'index');
?>
<div class="statistik">
    <div class="stat"><b><?= $jmlRuang ?></b><span>Ruang aktif</span></div>
    <div class="stat"><b><?= $kosongSekarang ?></b><span>Kosong 1 jam ke depan</span></div>
    <div class="stat"><b><?= $jmlJadwal ?></b><span>Jadwal kuliah mingguan</span></div>
    <div class="stat"><b><?= $jmlPinjamHariIni ?></b><span>Peminjaman hari ini</span></div>
</div>

<div class="kartu-admin">
    <h3>Mulai dari mana?</h3>
    <p class="muted" style="margin:0">
        Tambahkan data <a href="ruang.php">ruang</a>, isi <a href="jadwal.php">jadwal kuliah</a> mingguan,
        lalu catat <a href="peminjaman.php">peminjaman</a> sekali pakai (rapat, ujian, kegiatan).
        Halaman pencarian otomatis memakai ketiganya untuk menentukan ruang yang kosong.
    </p>
</div>
<?php admin_footer(); ?>
