<?php


const HARI = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
const BULAN = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
               7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

/* ---------- Bantu umum ---------- */

function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function valid_tanggal(string $t): bool
{
    $d = DateTime::createFromFormat('!Y-m-d', $t);
    return $d !== false && $d->format('Y-m-d') === $t;
}

function valid_jam(string $j): bool
{
    return (bool)preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $j);
}

/** "08:30" -> "08:30:00" */
function norm_jam(string $j): string
{
    return strlen($j) === 5 ? $j . ':00' : $j;
}

/** "08:30:00" -> "08:30" */
function jam_pendek(string $j): string
{
    return substr($j, 0, 5);
}

/** 1 (Senin) .. 7 (Minggu) */
function hari_iso(string $tanggal): int
{
    return (int)(new DateTime($tanggal))->format('N');
}

function tanggal_indo(string $tanggal): string
{
    $d = new DateTime($tanggal);
    return HARI[(int)$d->format('N')] . ', ' . $d->format('j') . ' ' . BULAN[(int)$d->format('n')] . ' ' . $d->format('Y');
}

/**
 * Jendela waktu "sekarang": mulai dari detik ini sampai $durasi menit ke depan
 * (dibatasi sampai 23:59:59 di hari yang sama).
 * @return array [tanggal, mulai, selesai]
 */
function jendela_sekarang(int $durasi): array
{
    $now     = new DateTime('now');
    $tanggal = $now->format('Y-m-d');
    $mulai   = $now->format('H:i:00');
    $akhir   = (clone $now)->modify('+' . $durasi . ' minutes');
    $selesai = $akhir->format('Y-m-d') === $tanggal ? $akhir->format('H:i:00') : '23:59:59';
    if ($selesai <= $mulai) {
        $selesai = '23:59:59';
    }
    return [$tanggal, $mulai, $selesai];
}

/* ---------- Data kesibukan ruang ---------- */

/**
 * Semua kegiatan (kuliah + peminjaman) pada suatu tanggal, dikelompokkan per ruang
 * dan terurut berdasarkan jam mulai.
 * @return array<int, array<int, array>>  [ruang_id => [kegiatan, ...]]
 */
function ambil_kesibukan(PDO $pdo, string $tanggal, ?int $ruangId = null): array
{
    $f1 = $ruangId ? ' AND ruang_id = :r1' : '';
    $f2 = $ruangId ? ' AND ruang_id = :r2' : '';

    $sql = "SELECT id, ruang_id, jam_mulai, jam_selesai, mata_kuliah AS judul, dosen AS sub, 'kuliah' AS jenis
              FROM jadwal WHERE hari = :hari{$f1}
            UNION ALL
            SELECT id, ruang_id, jam_mulai, jam_selesai, keperluan AS judul, peminjam AS sub, 'pinjam' AS jenis
              FROM peminjaman WHERE tanggal = :tanggal{$f2}
            ORDER BY ruang_id, jam_mulai";

    $p = [':hari' => hari_iso($tanggal), ':tanggal' => $tanggal];
    if ($ruangId) {
        $p[':r1'] = $ruangId;
        $p[':r2'] = $ruangId;
    }

    $st = $pdo->prepare($sql);
    $st->execute($p);

    $hasil = [];
    foreach ($st->fetchAll() as $row) {
        $hasil[(int)$row['ruang_id']][] = $row;
    }
    return $hasil;
}

/**
 * Cari ruang beserta status kosong/terisi pada rentang waktu tertentu.
 * Filter yang didukung: gedung, kapasitas (minimal), q (kata kunci).
 */
function cari_ruang(PDO $pdo, string $tanggal, string $mulai, string $selesai, array $f = []): array
{
    $where = ['r.aktif = 1'];
    $p = [];

    if (!empty($f['gedung'])) {
        $where[] = 'r.gedung = :gedung';
        $p[':gedung'] = $f['gedung'];
    }
    if (!empty($f['kapasitas']) && (int)$f['kapasitas'] > 0) {
        $where[] = 'r.kapasitas >= :kap';
        $p[':kap'] = (int)$f['kapasitas'];
    }
    if (!empty($f['q'])) {
        $like = '%' . addcslashes((string)$f['q'], '%_\\') . '%';
        $where[] = '(r.kode LIKE :q1 OR r.nama LIKE :q2 OR r.fasilitas LIKE :q3)';
        $p[':q1'] = $p[':q2'] = $p[':q3'] = $like;
    }

    $st = $pdo->prepare(
        'SELECT r.id, r.kode, r.nama, r.gedung, r.lantai, r.kapasitas, r.fasilitas
           FROM ruang r
          WHERE ' . implode(' AND ', $where) . '
          ORDER BY r.gedung, r.lantai, r.kode'
    );
    $st->execute($p);
    $daftar = $st->fetchAll();

    $sibuk = ambil_kesibukan($pdo, $tanggal);
    $hasil = [];

    foreach ($daftar as $r) {
        $id     = (int)$r['id'];
        $agenda = $sibuk[$id] ?? [];

        $sedang  = null;   // kegiatan pertama yang bentrok dengan rentang
        $berikut = null;   // kegiatan terdekat setelah rentang
        $akhir   = null;   // jam ruang benar-benar bebas lagi (rangkaian kegiatan beruntun)

        foreach ($agenda as $b) {
            if ($akhir === null) {
                if ($b['jam_mulai'] < $selesai && $b['jam_selesai'] > $mulai) {
                    $sedang = $b;
                    $akhir  = $b['jam_selesai'];
                } elseif ($b['jam_mulai'] >= $selesai && $berikut === null) {
                    $berikut = $b;
                }
            } elseif ($b['jam_mulai'] <= $akhir) {
                if ($b['jam_selesai'] > $akhir) {
                    $akhir = $b['jam_selesai'];
                }
            } else {
                break;
            }
        }

        $item = [
            'id'        => $id,
            'kode'      => $r['kode'],
            'nama'      => $r['nama'],
            'gedung'    => $r['gedung'],
            'lantai'    => (int)$r['lantai'],
            'kapasitas' => (int)$r['kapasitas'],
            'fasilitas' => array_values(array_filter(array_map('trim', explode(',', (string)$r['fasilitas'])), 'strlen')),
        ];

        if ($sedang === null) {
            $item['status']  = 'kosong';
            $item['berikut'] = $berikut ? [
                'judul'  => $berikut['judul'],
                'jenis'  => $berikut['jenis'],
                'mulai'  => jam_pendek($berikut['jam_mulai']),
                'selesai' => jam_pendek($berikut['jam_selesai']),
            ] : null;
            $item['kosong_sampai'] = $berikut ? jam_pendek($berikut['jam_mulai']) : null;
        } else {
            $item['status'] = 'terisi';
            $item['sedang'] = [
                'judul'  => $sedang['judul'],
                'sub'    => $sedang['sub'],
                'jenis'  => $sedang['jenis'],
                'mulai'  => jam_pendek($sedang['jam_mulai']),
                'selesai' => jam_pendek($sedang['jam_selesai']),
            ];
            $item['kosong_lagi'] = jam_pendek($akhir);
        }

        $hasil[] = $item;
    }

    return $hasil;
}

/* ---------- Deteksi bentrok untuk admin ---------- */

/** Cek bentrok jadwal kuliah mingguan. Mengembalikan deskripsi bentrok atau null. */
function bentrok_jadwal(PDO $pdo, int $ruang, int $hari, string $mulai, string $selesai, int $abaikanId = 0): ?string
{
    $st = $pdo->prepare(
        'SELECT mata_kuliah, jam_mulai, jam_selesai FROM jadwal
          WHERE ruang_id = :r AND hari = :h AND jam_mulai < :s AND jam_selesai > :m AND id <> :i
          LIMIT 1'
    );
    $st->execute([':r' => $ruang, ':h' => $hari, ':s' => $selesai, ':m' => $mulai, ':i' => $abaikanId]);
    $row = $st->fetch();
    return $row
        ? sprintf('%s (%s–%s)', $row['mata_kuliah'], jam_pendek($row['jam_mulai']), jam_pendek($row['jam_selesai']))
        : null;
}

/** Cek bentrok peminjaman terhadap peminjaman lain DAN jadwal kuliah pada hari itu. */
function bentrok_pinjam(PDO $pdo, int $ruang, string $tanggal, string $mulai, string $selesai, int $abaikanId = 0): ?string
{
    foreach (ambil_kesibukan($pdo, $tanggal, $ruang)[$ruang] ?? [] as $b) {
        if ($b['jenis'] === 'pinjam' && (int)$b['id'] === $abaikanId) {
            continue;
        }
        if ($b['jam_mulai'] < $selesai && $b['jam_selesai'] > $mulai) {
            return sprintf('%s (%s–%s)', $b['judul'], jam_pendek($b['jam_mulai']), jam_pendek($b['jam_selesai']));
        }
    }
    return null;
}
