<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $aksi = (string)($_POST['aksi'] ?? '');
    $id   = (int)($_POST['id'] ?? 0);

    if ($aksi === 'simpan') {
        $kode      = trim((string)($_POST['kode'] ?? ''));
        $nama      = trim((string)($_POST['nama'] ?? ''));
        $gedung    = trim((string)($_POST['gedung'] ?? ''));
        $lantai    = (int)($_POST['lantai'] ?? 1);
        $kapasitas = (int)($_POST['kapasitas'] ?? 0);
        $fasilitas = trim((string)($_POST['fasilitas'] ?? ''));
        $aktif     = isset($_POST['aktif']) ? 1 : 0;

        $err = [];
        if ($kode === '' || mb_strlen($kode) > 20)   $err[] = 'Kode wajib diisi (maks. 20 karakter).';
        if ($nama === '' || mb_strlen($nama) > 100)  $err[] = 'Nama wajib diisi (maks. 100 karakter).';
        if ($gedung === '' || mb_strlen($gedung) > 50) $err[] = 'Gedung wajib diisi (maks. 50 karakter).';
        if ($kapasitas < 1 || $kapasitas > 1000)     $err[] = 'Kapasitas harus 1–1000.';
        if ($lantai < 0 || $lantai > 50)             $err[] = 'Lantai tidak valid.';
        if (mb_strlen($fasilitas) > 255)             $err[] = 'Fasilitas terlalu panjang.';

        if ($err) {
            flash('err', implode(' ', $err));
            header('Location: ruang.php' . ($id ? '?edit=' . $id : ''));
            exit;
        }

        try {
            if ($id) {
                $pdo->prepare('UPDATE ruang SET kode=:kode, nama=:nama, gedung=:gedung, lantai=:lantai,
                               kapasitas=:kap, fasilitas=:fas, aktif=:aktif WHERE id=:id')
                    ->execute([':kode' => $kode, ':nama' => $nama, ':gedung' => $gedung, ':lantai' => $lantai,
                               ':kap' => $kapasitas, ':fas' => $fasilitas, ':aktif' => $aktif, ':id' => $id]);
            } else {
                $pdo->prepare('INSERT INTO ruang (kode, nama, gedung, lantai, kapasitas, fasilitas, aktif)
                               VALUES (:kode, :nama, :gedung, :lantai, :kap, :fas, :aktif)')
                    ->execute([':kode' => $kode, ':nama' => $nama, ':gedung' => $gedung, ':lantai' => $lantai,
                               ':kap' => $kapasitas, ':fas' => $fasilitas, ':aktif' => $aktif]);
            }
            flash('ok', 'Data ruang disimpan.');
        } catch (PDOException $e) {
            flash('err', $e->getCode() === '23000' ? 'Kode ruang sudah dipakai.' : 'Gagal menyimpan data ruang.');
            header('Location: ruang.php' . ($id ? '?edit=' . $id : ''));
            exit;
        }
    } elseif ($aksi === 'hapus' && $id) {
        $pdo->prepare('DELETE FROM ruang WHERE id = :id')->execute([':id' => $id]);
        flash('ok', 'Ruang dihapus beserta jadwal dan peminjamannya.');
    } elseif ($aksi === 'toggle' && $id) {
        $pdo->prepare('UPDATE ruang SET aktif = 1 - aktif WHERE id = :id')->execute([':id' => $id]);
        flash('ok', 'Status ruang diubah.');
    }

    header('Location: ruang.php');
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM ruang WHERE id = :id');
    $st->execute([':id' => (int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}

$daftar = $pdo->query('SELECT * FROM ruang ORDER BY gedung, lantai, kode')->fetchAll();

admin_header('Kelola ruang', 'ruang');
?>
<form method="post" class="kartu-admin">
    <h3><?= $edit ? 'Ubah ruang' : 'Tambah ruang' ?></h3>
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="simpan">
    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="form-grid">
        <div><label>Kode</label><input type="text" name="kode" maxlength="20" required value="<?= e($edit['kode'] ?? '') ?>"></div>
        <div><label>Nama ruang</label><input type="text" name="nama" maxlength="100" required value="<?= e($edit['nama'] ?? '') ?>"></div>
        <div><label>Gedung</label><input type="text" name="gedung" maxlength="50" required value="<?= e($edit['gedung'] ?? '') ?>"></div>
        <div><label>Lantai</label><input type="number" name="lantai" min="0" max="50" required value="<?= e($edit['lantai'] ?? 1) ?>"></div>
        <div><label>Kapasitas</label><input type="number" name="kapasitas" min="1" max="1000" required value="<?= e($edit['kapasitas'] ?? 40) ?>"></div>
        <div class="lebar"><label>Fasilitas (pisahkan dengan koma)</label>
            <input type="text" name="fasilitas" maxlength="255" placeholder="Proyektor, AC, Papan tulis" value="<?= e($edit['fasilitas'] ?? '') ?>"></div>
        <div><label class="cek"><input type="checkbox" name="aktif" <?= !$edit || $edit['aktif'] ? 'checked' : '' ?>> Ruang aktif (tampil di pencarian)</label></div>
        <div>
            <button class="tombol" type="submit">Simpan</button>
            <?php if ($edit): ?><a class="tombol sekunder" href="ruang.php">Batal</a><?php endif; ?>
        </div>
    </div>
</form>

<div class="kartu-admin tabel-bungkus">
    <table>
        <thead><tr><th>Kode</th><th>Nama</th><th>Gedung / Lantai</th><th>Kapasitas</th><th>Fasilitas</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($daftar as $r): ?>
            <tr>
                <td><b><?= e($r['kode']) ?></b></td>
                <td><?= e($r['nama']) ?></td>
                <td><?= e($r['gedung']) ?> / <?= (int)$r['lantai'] ?></td>
                <td><?= (int)$r['kapasitas'] ?></td>
                <td><?= e($r['fasilitas']) ?></td>
                <td><?= $r['aktif'] ? 'Aktif' : '<span class="muted">Nonaktif</span>' ?></td>
                <td class="aksi">
                    <a class="tombol sekunder kecil" href="ruang.php?edit=<?= (int)$r['id'] ?>">Ubah</a>
                    <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="tombol sekunder kecil" type="submit"><?= $r['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
                    <form method="post" onsubmit="return confirm('Hapus ruang ini beserta seluruh jadwal dan peminjamannya?')">
                        <?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="tombol bahaya kecil" type="submit">Hapus</button></form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$daftar): ?><tr><td colspan="7" class="muted">Belum ada ruang.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php admin_footer(); ?>
