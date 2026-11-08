<?php
/**
 * includes/functions.php — fungsi bantu umum (database, keamanan, format).
 */

/** Escape output HTML (cegah XSS). */
function e($v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Redirect lalu hentikan script. */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// ----------------------------------------------------------------
//  DATABASE (prepared statement) — semua query wajib lewat sini
// ----------------------------------------------------------------

/** Jalankan query dengan parameter. Tipe otomatis (i untuk int, d untuk float, s lainnya). */
function db_query(string $sql, array $params = [])
{
    global $conn;
    $stmt = $conn->prepare($sql);
    if ($params) {
        $types = '';
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
        }
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

function db_all(string $sql, array $params = []): array
{
    $res = db_query($sql, $params)->get_result();
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function db_one(string $sql, array $params = []): ?array
{
    $res = db_query($sql, $params)->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    return $row ?: null;
}

function db_value(string $sql, array $params = [])
{
    $row = db_one($sql, $params);
    return $row ? array_values($row)[0] : null;
}

/** INSERT/UPDATE/DELETE. Mengembalikan jumlah baris terpengaruh. */
function db_exec(string $sql, array $params = []): int
{
    return db_query($sql, $params)->affected_rows;
}

function db_last_id(): int
{
    global $conn;
    return (int)$conn->insert_id;
}

// ----------------------------------------------------------------
//  PENGATURAN PERPUSTAKAAN (tabel `pengaturan`)
// ----------------------------------------------------------------
function setting(string $key, $default = null)
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db_all("SELECT kunci, nilai FROM pengaturan") as $r) {
            $cache[$r['kunci']] = $r['nilai'];
        }
    }
    return $cache[$key] ?? $default;
}

// ----------------------------------------------------------------
//  CSRF — setiap form POST wajib menyertakan csrf_field()
// ----------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        flash('error', 'Sesi formulir kedaluwarsa. Silakan ulangi.');
        redirect(back_url('role.php'));
    }
}

// ----------------------------------------------------------------
//  FLASH MESSAGE (ditampilkan sebagai toast oleh assets/js/app.js)
// ----------------------------------------------------------------
function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function get_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ----------------------------------------------------------------
//  FORMAT
// ----------------------------------------------------------------
function rupiah($n): string
{
    return 'Rp ' . number_format((int)$n, 0, ',', '.');
}

function tgl($date, bool $withTime = false): string
{
    if (!$date) return '-';
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($date);
    $s = date('j', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t);
    return $withTime ? $s . ', ' . date('H:i', $t) : $s;
}

/** Selisih hari: positif = sudah lewat jatuh tempo. */
function hari_terlambat(?string $jatuhTempo, ?string $tanggal = null): int
{
    if (!$jatuhTempo) return 0;
    $a = new DateTime($jatuhTempo);
    $b = new DateTime($tanggal ?? 'today');
    return $b > $a ? (int)$a->diff($b)->days : 0;
}

function sisa_hari(?string $jatuhTempo): int
{
    if (!$jatuhTempo) return 0;
    $a = new DateTime('today');
    $b = new DateTime($jatuhTempo);
    $d = (int)$a->diff($b)->days;
    return $b >= $a ? $d : -$d;
}

/** URL cover buku; null jika tidak ada (tampilkan placeholder). */
function cover_url(?string $cover): ?string
{
    if (!$cover) return null;
    if (preg_match('#^https?://#', $cover)) return $cover;
    return is_file(BASE_DIR . '/' . $cover) ? $cover : null;
}

/** Label + kelas warna untuk status peminjaman. */
function status_badge(string $status): string
{
    $map = [
        'menunggu'         => ['Menunggu', 'bg-amber-100 text-amber-700'],
        'dipinjam'         => ['Dipinjam', 'bg-gold/20 text-gold-dark'],
        'diajukan_kembali' => ['Diajukan Kembali', 'bg-sky-100 text-sky-700'],
        'dikembalikan'     => ['Dikembalikan', 'bg-emerald-100 text-emerald-700'],
        'ditolak'          => ['Ditolak', 'bg-red-100 text-red-600'],
        'dibatalkan'       => ['Dibatalkan', 'bg-gray-100 text-gray-500'],
        'belum_dibayar'    => ['Belum Dibayar', 'bg-red-100 text-red-600'],
        'lunas'            => ['Lunas', 'bg-emerald-100 text-emerald-700'],
        'aktif'            => ['Aktif', 'bg-emerald-100 text-emerald-700'],
        'nonaktif'         => ['Nonaktif', 'bg-gray-100 text-gray-500'],
    ];
    [$label, $cls] = $map[$status] ?? [ucfirst($status), 'bg-gray-100 text-gray-600'];
    return '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ' . $cls . '">' . e($label) . '</span>';
}

/** Validasi aturan password (dipakai siswa & pustakawan). Mengembalikan pesan error atau null. */
function validasi_password(string $baru, string $konfirmasi): ?string
{
    if (strlen($baru) < 6) return 'Password baru minimal 6 karakter.';
    if (strlen($baru) > 72) return 'Password maksimal 72 karakter.';
    if (!preg_match('/[A-Za-z]/', $baru) || !preg_match('/\d/', $baru)) {
        return 'Password baru harus mengandung huruf dan angka.';
    }
    if ($baru !== $konfirmasi) return 'Konfirmasi password tidak sama.';
    return null;
}

/** Upload cover (jpg/png/webp, maks 2MB). Mengembalikan path relatif atau null. */
function upload_cover(array $file): ?string
{
    if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload cover gagal.');
    if ($file['size'] > 2 * 1024 * 1024) throw new RuntimeException('Ukuran cover maksimal 2 MB.');
    $info = @getimagesize($file['tmp_name']);
    $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$info[2] ?? 0] ?? null;
    if (!$ext) throw new RuntimeException('Cover harus berupa gambar JPG, PNG, atau WEBP.');
    $name = 'uploads/cover/' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], BASE_DIR . '/' . $name)) {
        throw new RuntimeException('Gagal menyimpan cover. Periksa izin folder uploads/cover.');
    }
    return $name;
}

/** URL kembali yang aman (hanya halaman lokal dari referer). */
function back_url(string $default): string
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $u = parse_url($ref);
    if (!$u || empty($u['path'])) return $default;
    if (!empty($u['host']) && $u['host'] !== ($_SERVER['HTTP_HOST'] ?? '') && $u['host'] . ':' . ($u['port'] ?? '') !== ($_SERVER['HTTP_HOST'] ?? '')) return $default;
    $file = basename($u['path']);
    if (!preg_match('/^[a-z_]+\.php$/', $file)) return $default;
    return $file . (isset($u['query']) ? '?' . $u['query'] : '') . (isset($u['fragment']) ? '#' . $u['fragment'] : '');
}
