<?php
require_once __DIR__ . '/../includes/auth.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $user = trim((string)($_POST['username'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');

    $st = db()->prepare('SELECT id, password_hash FROM admin WHERE username = :u LIMIT 1');
    $st->execute([':u' => $user]);
    $row = $st->fetch();

    if ($row && password_verify($pass, $row['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id']   = (int)$row['id'];
        $_SESSION['admin_user'] = $user;
        header('Location: index.php');
        exit;
    }
    usleep(400000); // perlambat tebakan password
    $error = 'Username atau password salah.';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk admin · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="login-box">
    <form method="post" class="kartu-admin">
        <h1>Masuk admin</h1>
        <?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
        <?= csrf_field() ?>
        <div>
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus autocomplete="username">
        </div>
        <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <button type="submit" class="tombol">Masuk</button>
        <a href="../index.php" class="muted">← Kembali ke pencarian</a>
    </form>
</div>
</body>
</html>
