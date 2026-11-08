<?php
/**
 * config.php — konfigurasi utama aplikasi.
 * Cukup ubah bagian "KONEKSI DATABASE" sesuai XAMPP/Laragon Anda.
 */

// ===================== KONEKSI DATABASE =====================
$host     = "localhost";
$user     = "root";      // username MySQL
$password = "";          // password MySQL (XAMPP default kosong)
$database = "library";   // nama database (lihat library.sql)

// ===================== PENGATURAN APLIKASI ==================
define('APP_NAME',   'Perpus Digital');
define('APP_SCHOOL', 'SMKN 1 Kamal');
define('BASE_DIR',   __DIR__);

date_default_timezone_set('Asia/Jakarta');

// Sesi aman: cookie httponly + samesite
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_name('PERPUSSESSID');
    session_start();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $user, $password, $database);
    $conn->set_charset('utf8mb4');
    $conn->query("SET time_zone = '+07:00'");
} catch (mysqli_sql_exception $ex) {
    http_response_code(500);
    die('<div style="font-family:sans-serif;padding:2rem;background:#F5F5DC;color:#333">'
        . '<h2>Koneksi database gagal</h2><p>' . htmlspecialchars($ex->getMessage()) . '</p>'
        . '<p>Pastikan MySQL berjalan dan file <b>library.sql</b> sudah di-import.</p></div>');
}

require_once BASE_DIR . '/includes/functions.php';
require_once BASE_DIR . '/includes/auth.php';
