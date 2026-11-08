<?php
require 'config.php';

// Hapus seluruh data sesi (siswa maupun pustakawan)
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

// Mulai sesi baru hanya untuk menampilkan pesan
session_start();
flash('success', 'Anda berhasil logout. Sampai jumpa lagi!');
redirect('role.php');
