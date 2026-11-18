<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();

$pdo = db();
$hariIni = date('Y-m-d');
$ruangList = $pdo->query('SELECT id, kode, nama FROM ruang ORDER BY kode')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $aksi = (string)($_POST['aksi'] ?? '');
    $id   = (int)($_POST['id'] ?? 0);

    if ($aksi === 'simpan') {
        $ruang    = (int)($_POST['ruang_id'] ?? 0);
        $tanggal  = (string)($_POST['tanggal'] ?? '');
        $mulai    = (string)($_POST['jam_mulai'] ?? '');
        $selesai  = (string)($_POST['jam_selesai'] ?? '');
        $keperluan = trim((string)($_POST['keperluan'] ?? ''));
        $peminjam = trim((string)($_POST['peminjam'] ?? ''));

        $balik = 'peminjaman.php' . ($id ? '?edit=' . $id : '');
        $err = null;

        if (!$ruang)                                   $err = 'Pilih ruang.';
        elseif (!valid_tanggal($tanggal))              $err = 'Tanggal tidak valid.';
        elseif (!valid_jam($mulai) || !valid_jam($selesai)) $err = 'Jam tidak valid.';
        elseif ($keperluan === '' || mb_strlen($keperluan) > 150) $err = 'Keperluan wajib diisi (maks. 150 karakter).';
        elseif (mb_strlen($peminjam) > 100)            $err = 'Nama peminjam terlalu panjang.';

        if (!$err) {
            $mulai = norm_jam($mulai);
            $selesai = norm_jam($selesai);
            if ($selesai <= $mulai) {
                $err = 'Jam selesai harus setelah jam mulai.';
            } elseif ($b = bentrok_pinjam($pdo, $ruang, $tanggal, $mulai, $selesai, $id)) {
                $err = 'Bentrok dengan kegiatan lain di ruang ini: ' . $b . '.';
            }
        }

        if ($err) {
            flash('err', $err);
            header('Location: ' . $balik);
            exit;
        }

        if ($id) {
            $pdo->prepare('UPDATE peminjaman SET ruang_id=:r, tanggal=:t, jam_mulai=:m, jam_selesai=:s, keperluan=:k, peminjam=:p WHERE id=:id')
                ->execute([':r' => $ruang, ':t' => $tanggal, ':m' => $mulai, ':s' => $selesai, ':k' => $keperluan, ':p' => $peminjam, ':id' => $id]);
        } else {
            $pdo->prepare('INSERT INTO peminjaman (ruang_id, tanggal, jam_mulai, jam_selesai, keperluan, peminjam) VALUES (:r, :t, :m, :s, :k, :p)')
                ->execute([':r' => $ruang, ':t' => $tanggal, ':m' => $mulai, ':s' => $selesai, ':k' => $keperluan, ':p' => $peminjam]);
        }
        flash('ok', 'Peminjaman disimpan.');
    } elseif ($aksi === 'hapus' && $id) {
        $pdo->prepare('DELETE FROM peminjaman WHERE id = :id')->execute([':id' => $id]);
        flash('ok', 'Peminjaman dihapus.');
    } elseif ($aksi === 'bersihkan') {
        $st = $pdo->prepare('DELETE FROM peminjaman WHERE tanggal < :t');
        $st->execute([':t' => $hariIni]);
        flash('ok', $st->rowCount() . ' peminjaman yang sudah lewat dihapus.');
    }

    header('Location: peminjaman.php' . (!empty($_POST['semua']) ? '?semua=1' : ''));
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM peminjaman WHERE id = :id');
    $st->execute([':id' => (int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}

$semua = !empty($_GET['semua']);
$sql = 'SELECT p.*, r.kode FROM peminjaman p JOIN ruang r ON r.id = p.ruang_id';
$prm = [];
if (!$semua) {
    $sql .= ' WHERE p.tanggal >= :t';
    $prm[':t'] = $hariIni;
}
$sql .= ' ORDER BY p.tanggal, p.jam_mulai, r.kode';
$st = $pdo->prepare($sql);
$st->execute($prm);
$daftar = $st->fetchAll();

admin_header('Peminjaman ruang', 'peminjaman');
?>
<form method="post" class="kartu-admin">
    <h3><?= $edit ? 'Ubah peminjaman' : 'Tambah peminjaman' ?></h3>
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="simpan">
    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <input type="hidden" name="semua" value="<?= $semua ? 1 : 0 ?>">
    <div class="form-grid">
        <div><label>Ruang</label>
            <select name="ruang_id" required>
                <option value="">— pilih —</option>
                <?php foreach ($ruangList as $r): ?>
                    <option value="<?= (int)$r['id'] ?>" <?= (int)($edit['ruang_id'] ?? 0) === (int)$r['id'] ? 'selected' : '' ?>>
                        <?= e($r['kode']) ?> — <?= e($r['nama']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div><label>Tanggal</label><input type="date" name="tanggal" required value="<?= e($edit['tanggal'] ?? $hariIni) ?>"></div>
        <div><label>Jam mulai</label><input type="time" name="jam_mulai" required value="<?= e(isset($edit['jam_mulai']) ? jam_pendek($edit['jam_mulai']) : '08:00') ?>"></div>
        <div><label>Jam selesai</label><input type="time" name="jam_selesai" required value="<?= e(isset($edit['jam_selesai']) ? jam_pendek($edit['jam_selesai']) : '10:00') ?>"></div>
        <div><label>Keperluan</label><input type="text" name="keperluan" maxlength="150" required placeholder="Rapat, ujian, seminar…" value="<?= e($edit['keperluan'] ?? '') ?>"></div>
        <div><label>Peminjam (opsional)</label><input type="text" name="peminjam" maxlength="100" value="<?= e($edit['peminjam'] ?? '') ?>"></div>
        <div>
            <button class="tombol" type="submit">Simpan</button>
            <?php if ($edit): ?><a class="tombol sekunder" href="peminjaman.php">Batal</a><?php endif; ?>
        </div>
    </div>
</form>

<div class="kartu-admin">
    <div style="display:flex;gap:10px;justify-content:space-between;flex-wrap:wrap;margin-bottom:12px">
        <div>
            <?php if ($semua): ?>
                <a class="tombol sekunder kecil" href="peminjaman.php">Tampilkan yang akan datang saja</a>
            <?php else: ?>
                <a class="tombol sekunder kecil" href="peminjaman.php?semua=1">Tampilkan semua termasuk yang lewat</a>
            <?php endif; ?>
        </div>
        <form method="post" onsubmit="return confirm('Hapus semua peminjaman sebelum hari ini?')">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="bersihkan"><input type="hidden" name="semua" value="<?= $semua ? 1 : 0 ?>">
            <button class="tombol sekunder kecil" type="submit">Bersihkan yang sudah lewat</button>
        </form>
    </div>
    <div class="tabel-bungkus">
        <table>
            <thead><tr><th>Tanggal</th><th>Jam</th><th>Ruang</th><th>Keperluan</th><th>Peminjam</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($daftar as $p): ?>
                <tr>
                    <td><?= e(tanggal_indo($p['tanggal'])) ?></td>
                    <td><?= e(jam_pendek($p['jam_mulai'])) ?>–<?= e(jam_pendek($p['jam_selesai'])) ?></td>
                    <td><b><?= e($p['kode']) ?></b></td>
                    <td><?= e($p['keperluan']) ?></td>
                    <td><?= e($p['peminjam']) ?></td>
                    <td class="aksi">
                        <a class="tombol sekunder kecil" href="peminjaman.php?edit=<?= (int)$p['id'] ?><?= $semua ? '&semua=1' : '' ?>">Ubah</a>
                        <form method="post" onsubmit="return confirm('Hapus peminjaman ini?')">
                            <?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <input type="hidden" name="semua" value="<?= $semua ? 1 : 0 ?>">
                            <button class="tombol bahaya kecil" type="submit">Hapus</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$daftar): ?><tr><td colspan="6" class="muted">Tidak ada peminjaman.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php admin_footer(); ?>
