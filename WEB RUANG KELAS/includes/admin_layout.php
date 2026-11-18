<?php
/**
 * Kerangka halaman admin: admin_header() dan admin_footer().
 */
function admin_header(string $judul, string $aktif = ''): void
{
    $menu = [
        'index'      => ['index.php', 'Dasbor'],
        'ruang'      => ['ruang.php', 'Ruang'],
        'jadwal'     => ['jadwal.php', 'Jadwal kuliah'],
        'peminjaman' => ['peminjaman.php', 'Peminjaman'],
    ];
    ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul) ?> · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<nav class="admin-nav">
    <div class="wrap">
        <strong>Admin</strong>
        <?php foreach ($menu as $kunci => [$url, $label]): ?>
            <a href="<?= e($url) ?>" class="<?= $kunci === $aktif ? 'aktif' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
        <span class="kanan">
            <a href="../index.php">Lihat situs</a>
            <a href="logout.php">Keluar (<?= e($_SESSION['admin_user'] ?? '') ?>)</a>
        </span>
    </div>
</nav>
<main class="wrap admin-main">
    <h2><?= e($judul) ?></h2>
    <?php flash_tampil(); ?>
    <?php
}

function admin_footer(): void
{
    ?>
</main>
</body>
</html>
    <?php
}