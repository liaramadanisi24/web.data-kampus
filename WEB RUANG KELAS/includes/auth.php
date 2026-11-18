<?php
/**
 * Sesi admin, token CSRF, dan pesan flash.
 */
require_once __DIR__ . '/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('rk_admin');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function require_admin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $kirim = (string)($_POST['csrf'] ?? '');
    if (!hash_equals((string)($_SESSION['csrf'] ?? ''), $kirim)) {
        http_response_code(400);
        exit('Token keamanan tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}

function flash(string $tipe, string $pesan): void
{
    $_SESSION['flash'][] = [$tipe, $pesan];
}

function flash_tampil(): void
{
    foreach ($_SESSION['flash'] ?? [] as [$tipe, $pesan]) {
        echo '<div class="flash ' . e($tipe) . '">' . e($pesan) . '</div>';
    }
    unset($_SESSION['flash']);
}
