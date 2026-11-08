<?php
/**
 * includes/auth.php — semua urusan login, sesi dan password.
 *
 * ATURAN PASSWORD (satu-satunya yang dipakai di aplikasi ini):
 *   - Disimpan dengan password_hash($pass, PASSWORD_DEFAULT)  -> bcrypt
 *   - Dicek dengan password_verify($pass, $hash)
 *   - Hash lama otomatis diperbarui (password_needs_rehash)
 *   - Tidak ada lagi file generate_hash.php / hash.php / password_hash.php
 *
 * SESI:
 *   $_SESSION['siswa'] = ['id', 'nama', 'nis']          -> login siswa (tabel anggota)
 *   $_SESSION['staf']  = ['id', 'nama', 'username', 'role'] -> login pustakawan (tabel users)
 */

const LOGIN_MAX_GAGAL  = 5;    // percobaan
const LOGIN_JEDA_DETIK = 60;   // detik dikunci setelah gagal berturut-turut
const SESI_IDLE_DETIK  = 7200; // logout otomatis jika 2 jam tidak aktif

// Logout otomatis bila sesi idle terlalu lama
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > SESI_IDLE_DETIK) {
    unset($_SESSION['siswa'], $_SESSION['staf']);
    $_SESSION['flash'][] = ['type' => 'info', 'msg' => 'Sesi berakhir karena tidak aktif. Silakan login kembali.'];
}
$_SESSION['last_activity'] = time();

function siswa(): ?array { return $_SESSION['siswa'] ?? null; }
function staf(): ?array  { return $_SESSION['staf'] ?? null; }

/** Halaman khusus siswa. */
function require_siswa(bool $izinkanWajibGanti = false): array
{
    $s = siswa();
    if (!$s) {
        flash('info', 'Silakan login sebagai siswa terlebih dahulu.');
        redirect('login_siswa.php');
    }
    // cek status & kewajiban ganti password setiap request (bisa berubah oleh pustakawan)
    $row = db_one("SELECT status, harus_ganti_password FROM anggota WHERE id_anggota = ?", [(int)$s['id']]);
    if (!$row || $row['status'] !== 'aktif') {
        unset($_SESSION['siswa']);
        flash('error', 'Akun Anda tidak aktif. Hubungi pustakawan.');
        redirect('login_siswa.php');
    }
    if ($row['harus_ganti_password'] && !$izinkanWajibGanti) {
        redirect('ganti_password.php');
    }
    return $s;
}

/** Halaman khusus pustakawan/admin. */
function require_staf(?string $role = null): array
{
    $s = staf();
    if (!$s) {
        flash('info', 'Silakan login sebagai pustakawan terlebih dahulu.');
        redirect('login_admin.php');
    }
    $row = db_one("SELECT status FROM users WHERE id_user = ?", [(int)$s['id']]);
    if (!$row || $row['status'] !== 'aktif') {
        unset($_SESSION['staf']);
        flash('error', 'Akun pustakawan tidak aktif.');
        redirect('login_admin.php');
    }
    if ($role && $s['role'] !== $role) {
        flash('error', 'Anda tidak memiliki akses ke menu tersebut.');
        redirect('dashboard_admin.php');
    }
    return $s;
}

// ----------------------------------------------------------------
//  Pembatas percobaan login (per jenis login, disimpan di sesi)
// ----------------------------------------------------------------
function login_terkunci(string $jenis): int
{
    $d = $_SESSION['login_gagal'][$jenis] ?? null;
    if ($d && $d['n'] >= LOGIN_MAX_GAGAL) {
        $sisa = LOGIN_JEDA_DETIK - (time() - $d['t']);
        if ($sisa > 0) return $sisa;
        unset($_SESSION['login_gagal'][$jenis]);
    }
    return 0;
}

function login_gagal(string $jenis): void
{
    $d = $_SESSION['login_gagal'][$jenis] ?? ['n' => 0, 't' => time()];
    $d['n']++;
    $d['t'] = time();
    $_SESSION['login_gagal'][$jenis] = $d;
}

function login_reset(string $jenis): void
{
    unset($_SESSION['login_gagal'][$jenis]);
}

/** Verifikasi password + upgrade hash bila perlu. */
function cek_password(string $plain, string $hash, string $tabel, string $kolomId, int $id): bool
{
    if (!password_verify($plain, $hash)) return false;
    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        $tabel = $tabel === 'users' ? 'users' : 'anggota';
        $kolomId = $tabel === 'users' ? 'id_user' : 'id_anggota';
        db_exec("UPDATE $tabel SET password = ? WHERE $kolomId = ?", [password_hash($plain, PASSWORD_DEFAULT), $id]);
    }
    return true;
}

function hash_password(string $plain): string
{
    return password_hash($plain, PASSWORD_DEFAULT);
}
