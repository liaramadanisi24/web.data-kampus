<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pdo = db();
$daftarGedung = $pdo->query('SELECT DISTINCT gedung FROM ruang WHERE aktif = 1 ORDER BY gedung')->fetchAll(PDO::FETCH_COLUMN);

$now = new DateTime('now');
$mulaiDefault = $now->format('H:i');
$akhir = (clone $now)->modify('+2 hours');
$selesaiDefault = $akhir->format('Y-m-d') === $now->format('Y-m-d') ? $akhir->format('H:i') : '23:59';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content= "width=device-width, initial-scale=1">
    <title><?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body data-refresh="<?= (int)REFRESH_DETIK ?>"
      data-epoch="<?= time() ?>"
      data-utcoffset="<?= (int)($now->getOffset() / 60) ?>">

<header class="topbar">
    <div class="wrap topbar-in">
        <div class="brand">
            <span class="logo" aria-hidden="true">▦</span>
            <div>
                <h1><?= e(APP_NAME) ?></h1>
                <p>Pantau ketersediaan ruang kelas secara langsung</p>
            </div>
        </div>
        <div class="jam-box">
            <div id="jam" class="jam" aria-live="off">--:--:--</div>
            <div class="tgl"><?= e(tanggal_indo($now->format('Y-m-d'))) ?></div>
        </div>
    </div>
</header>

<main class="wrap">
    <form id="form" class="panel" autocomplete="off">
        <input type="hidden" name="mode" id="mode" value="now">

        <div class="baris-atas">
            <div class="segmen" role="group" aria-label="Mode waktu">
                <button type="button" data-mode="now" aria-pressed="true">Sekarang</button>
                <button type="button" data-mode="custom" aria-pressed="false">Pilih waktu</button>
            </div>

            <div class="grup" id="panel-now">
                <label for="durasi">Kosong selama</label>
                <select name="durasi" id="durasi">
                    <option value="15">15 menit ke depan</option>
                    <option value="30">30 menit ke depan</option>
                    <option value="60" selected>1 jam ke depan</option>
                    <option value="90">1,5 jam ke depan</option>
                    <option value="120">2 jam ke depan</option>
                    <option value="180">3 jam ke depan</option>
                </select>
            </div>

            <div class="grup grup-waktu" id="panel-custom" hidden>
                <div>
                    <label for="tanggal">Tanggal</label>
                    <input type="date" name="tanggal" id="tanggal" value="<?= e($now->format('Y-m-d')) ?>" required>
                </div>
                <div>
                    <label for="mulai">Mulai</label>
                    <input type="time" name="mulai" id="mulai" value="<?= e($mulaiDefault) ?>" required>
                </div>
                <div>
                    <label for="selesai">Selesai</label>
                    <input type="time" name="selesai" id="selesai" value="<?= e($selesaiDefault) ?>" required>
                </div>
            </div>
        </div>

        <div class="baris-filter">
            <div>
                <label for="q">Cari</label>
                <input type="search" name="q" id="q" placeholder="Kode, nama, atau fasilitas (mis. proyektor)">
            </div>
            <div>
                <label for="gedung">Gedung</label>
                <select name="gedung" id="gedung">
                    <option value="">Semua gedung</option>
                    <?php foreach ($daftarGedung as $g): ?>
                        <option value="<?= e($g) ?>"><?= e($g) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="kapasitas">Kapasitas minimal</label>
                <input type="number" name="kapasitas" id="kapasitas" min="0" max="1000" step="5" placeholder="0">
            </div>
            <div>
                <label for="status">Tampilkan</label>
                <select name="status" id="status">
                    <option value="kosong" selected>Hanya yang kosong</option>
                    <option value="semua">Semua ruang</option>
                    <option value="terisi">Hanya yang terisi</option>
                </select>
            </div>
        </div>

        <div class="baris-bawah">
            <label class="cek"><input type="checkbox" id="auto" checked> Perbarui otomatis tiap <?= (int)REFRESH_DETIK ?> detik</label>
            <span id="status-muat" class="muted" role="status">Memuat…</span>
            <button type="submit" class="tombol">Cari sekarang</button>
        </div>
    </form>

    <section aria-live="polite">
        <div class="ringkas-bar">
            <div id="ringkasan" class="ringkas"></div>
            <div id="konteks" class="muted"></div>
        </div>
        <div id="hasil" class="grid"></div>
        <noscript><p class="kosong-msg">Aktifkan JavaScript agar hasil pencarian dapat ditampilkan.</p></noscript>
    </section>
</main>

<footer class="wrap foot">
    <span><?= e(APP_NAME) ?></span>
    <a href="admin/login.php">Masuk admin</a>
</footer>

<script src="assets/app.js"></script>
</body>
</html>
