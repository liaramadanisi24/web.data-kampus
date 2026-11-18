<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();

$pdo = db();
$ruangList = $pdo->query('SELECT id, kode, nama FROM ruang ORDER BY kode')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $aksi = (string)($_POST['aksi'] ?? '');
    $id   = (int)($_POST['id'] ?? 0);

    if ($aksi === 'simpan') {
        $ruang   = (int)($_POST['ruang_id'] ?? 0);
        $hari    = (int)($_POST['hari'] ?? 0);
        $mulai   = (string)($_POST['jam_mulai'] ?? '');
        $selesai = (string)($_POST['jam_selesai'] ?? '');
        $mk      = trim((string)($_POST['mata_kuliah'] ?? ''));
        $dosen   = trim((string)($_POST['dosen'] ?? ''));

        $balik = 'jadwal.php' . ($id ? '?edit=' . $id : '');
        $err = null;

        if (!$ruang)                                  $err = 'Pilih ruang.';
        elseif ($hari < 1 || $hari > 7)               $err = 'Pilih hari.';
        elseif (!valid_jam($mulai) || !valid_jam($selesai)) $err = 'Jam tidak valid.';
        elseif ($mk === '' || mb_strlen($mk) > 120)   $err = 'Mata kuliah wajib diisi (maks. 120 karakter).';
        elseif (mb_strlen($dosen) > 100)              $err = 'Nama dosen terlalu panjang.';

        if (!$err) {
            $mulai = norm_jam($mulai);
            $selesai = norm_jam($selesai);
            if ($selesai <= $mulai) {
                $err = 'Jam selesai harus setelah jam mulai.';
            } elseif ($b = bentrok_jadwal($pdo, $ruang, $hari, $mulai, $selesai, $id)) {
                $err = 'Bentrok dengan jadwal lain di ruang ini: ' . $b . '.';
            }
        }

        if ($err) {
            flash('err', $err);
            header('Location: ' . $balik);
            exit;
        }

        if ($id) {
            $pdo->prepare('UPDATE jadwal SET ruang_id=:r, hari=:h, jam_mulai=:m, jam_selesai=:s, mata_kuliah=:mk, dosen=:d WHERE id=:id')
                ->execute([':r' => $ruang, ':h' => $hari, ':m' => $mulai, ':s' => $selesai, ':mk' => $mk, ':d' => $dosen, ':id' => $id]);
        } else {
            $pdo->prepare('INSERT INTO jadwal (ruang_id, hari, jam_mulai, jam_selesai, mata_kuliah, dosen) VALUES (:r, :h, :m, :s, :mk, :d)')
                ->execute([':r' => $ruang, ':h' => $hari, ':m' => $mulai, ':s' => $selesai, ':mk' => $mk, ':d' => $dosen]);
        }
        flash('ok', 'Jadwal disimpan.');
    } elseif ($aksi === 'hapus' && $id) {
        $pdo->prepare('DELETE FROM jadwal WHERE id = :id')->execute([':id' => $id]);
        flash('ok', 'Jadwal dihapus.');
    }

    header('Location: jadwal.php' . (!empty($_POST['filter_ruang']) ? '?ruang=' . (int)$_POST['filter_ruang'] : ''));
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM jadwal WHERE id = :id');
    $st->execute([':id' => (int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}

$filterRuang = (int)($_GET['ruang'] ?? 0);
$sql = 'SELECT j.*, r.kode FROM jadwal j JOIN ruang r ON r.id = j.ruang_id';
$p = [];
if ($filterRuang) {
    $sql .= ' WHERE j.ruang_id = :r';
    $p[':r'] = $filterRuang;
}
$sql .= ' ORDER BY r.kode, j.hari, j.jam_mulai';
$st = $pdo->prepare($sql);
$st->execute($p);
$daftar = $st->fetchAll();

admin_header('Jadwal kuliah mingguan', 'jadwal');
?>
<form method="post" class="kartu-admin">
    <h3><?= $edit ? 'Ubah jadwal' : 'Tambah jadwal' ?></h3>
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="simpan">
    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <input type="hidden" name="filter_ruang" value="<?= $filterRuang ?>">
    <div class="form-grid">
        <div><label>Ruang</label>
            <select name="ruang_id" required>
                <option value="">— pilih —</option>
                <?php foreach ($ruangList as $r): ?>
                    <option value="<?= (int)$r['id'] ?>" <?= (int)($edit['ruang_id'] ?? $filterRuang) === (int)$r['id'] ? 'selected' : '' ?>>
                        <?= e($r['kode']) ?> — <?= e($r['nama']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div><label>Hari</label>
            <select name="hari" required>
                <?php foreach (HARI as $n => $nama): ?>
                    <option value="<?= $n ?>" <?= (int)($edit['hari'] ?? 1) === $n ? 'selected' : '' ?>><?= e($nama) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div><label>Jam mulai</label><input type="time" name="jam_mulai" required value="<?= e(isset($edit['jam_mulai']) ? jam_pendek($edit['jam_mulai']) : '07:30') ?>"></div>
        <div><label>Jam selesai</label><input type="time" name="jam_selesai" required value="<?= e(isset($edit['jam_selesai']) ? jam_pendek($edit['jam_selesai']) : '09:10') ?>"></div>
        <div><label>Mata kuliah</label><input type="text" name="mata_kuliah" maxlength="120" required value="<?= e($edit['mata_kuliah'] ?? '') ?>"></div>
        <div><label>Dosen (opsional)</label><input type="text" name="dosen" maxlength="100" value="<?= e($edit['dosen'] ?? '') ?>"></div>
        <div>
            <button class="tombol" type="submit">Simpan</button>
            <?php if ($edit): ?><a class="tombol sekunder" href="jadwal.php">Batal</a><?php endif; ?>
        </div>
    </div>
</form>

<div class="kartu-admin">
    <form method="get" class="form-grid" style="margin-bottom:12px">
        <div><label>Filter ruang</label>
            <select name="ruang" onchange="this.form.submit()">
                <option value="">Semua ruang</option>
                <?php foreach ($ruangList as $r): ?>
                    <option value="<?= (int)$r['id'] ?>" <?= $filterRuang === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['kode']) ?></option>
                <?php endforeach; ?>
            </select></div>
    </form>
    <div class="tabel-bungkus">
        <table>
            <thead><tr><th>Ruang</th><th>Hari</th><th>Jam</th><th>Mata kuliah</th><th>Dosen</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($daftar as $j): ?>
                <tr>
                    <td><b><?= e($j['kode']) ?></b></td>
                    <td><?= e(HARI[(int)$j['hari']] ?? '') ?></td>
                    <td><?= e(jam_pendek($j['jam_mulai'])) ?>–<?= e(jam_pendek($j['jam_selesai'])) ?></td>
                    <td><?= e($j['mata_kuliah']) ?></td>
                    <td><?= e($j['dosen']) ?></td>
                    <td class="aksi">
                        <a class="tombol sekunder kecil" href="jadwal.php?edit=<?= (int)$j['id'] ?>">Ubah</a>
                        <form method="post" onsubmit="return confirm('Hapus jadwal ini?')">
                            <?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$j['id'] ?>">
                            <input type="hidden" name="filter_ruang" value="<?= $filterRuang ?>">
                            <button class="tombol bahaya kecil" type="submit">Hapus</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$daftar): ?><tr><td colspan="6" class="muted">Belum ada jadwal.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php admin_footer(); ?>
