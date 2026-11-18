<?php
/**
 * Endpoint JSON pencarian ruang kosong.
 *
 * Parameter GET:
 *   mode      now | custom            (default now)
 *   durasi    menit, mode now         (default 60, 15-480)
 *   tanggal   YYYY-MM-DD, mode custom
 *   mulai     HH:MM, mode custom
 *   selesai   HH:MM, mode custom
 *   gedung    nama gedung (opsional)
 *   kapasitas kapasitas minimal (opsional)
 *   q         kata kunci kode/nama/fasilitas (opsional)
 *   status    kosong | terisi | semua (default semua)
 */
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function keluar(array $data, int $kode = 200): void
{
    http_response_code($kode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$mode = (($_GET['mode'] ?? 'now') === 'custom') ? 'custom' : 'now';

if ($mode === 'now') {
    $durasi = max(15, min(480, (int)($_GET['durasi'] ?? 60)));
    [$tanggal, $mulai, $selesai] = jendela_sekarang($durasi);
} else {
    $tanggal = (string)($_GET['tanggal'] ?? '');
    $mulai   = (string)($_GET['mulai'] ?? '');
    $selesai = (string)($_GET['selesai'] ?? '');

    if (!valid_tanggal($tanggal) || !valid_jam($mulai) || !valid_jam($selesai)) {
        keluar(['error' => 'Tanggal atau jam tidak valid.'], 422);
    }
    $mulai   = norm_jam($mulai);
    $selesai = norm_jam($selesai);
    if ($selesai <= $mulai) {
        keluar(['error' => 'Jam selesai harus setelah jam mulai.'], 422);
    }
}

$filter = [
    'gedung'    => mb_substr(trim((string)($_GET['gedung'] ?? '')), 0, 50),
    'kapasitas' => max(0, min(10000, (int)($_GET['kapasitas'] ?? 0))),
    'q'         => mb_substr(trim((string)($_GET['q'] ?? '')), 0, 60),
];
$status = (string)($_GET['status'] ?? 'semua');
if (!in_array($status, ['kosong', 'terisi', 'semua'], true)) {
    $status = 'semua';
}

try {
    $semua = cari_ruang(db(), $tanggal, $mulai, $selesai, $filter);
} catch (PDOException $e) {
    keluar(['error' => 'Terjadi kesalahan saat membaca database.'], 500);
}

$jmlKosong = count(array_filter($semua, fn($r) => $r['status'] === 'kosong'));
$tampil = $status === 'semua'
    ? $semua
    : array_filter($semua, fn($r) => $r['status'] === $status);

// Ruang kosong ditampilkan lebih dulu agar langsung terlihat.
usort($tampil, fn($a, $b) => [$a['status'] !== 'kosong'] <=> [$b['status'] !== 'kosong']);

$now = new DateTime('now');
keluar([
    'mode'            => $mode,
    'tanggal'         => $tanggal,
    'tanggal_label'   => tanggal_indo($tanggal),
    'mulai'           => jam_pendek($mulai),
    'selesai'         => jam_pendek($selesai),
    'server_epoch'    => time(),
    'utc_offset_menit' => (int)($now->getOffset() / 60),
    'total'           => count($semua),
    'kosong'          => $jmlKosong,
    'terisi'          => count($semua) - $jmlKosong,
    'ruang'           => array_values($tampil),
]);
