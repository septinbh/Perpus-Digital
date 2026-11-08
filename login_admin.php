<?php
/**
 * login_admin.php — Login pustakawan / admin (tabel `users`).
 * Alur: username + password -> cek role & status -> dashboard_admin.php
 */
require 'config.php';

if (staf()) redirect('dashboard_admin.php');

$error = '';
$old   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old  = trim($_POST['username'] ?? '');
    $pass = (string)($_POST['password'] ?? '');

    if ($sisa = login_terkunci('staf')) {
        $error = "Terlalu banyak percobaan gagal. Coba lagi dalam $sisa detik.";
    } elseif ($old === '' || $pass === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $u = db_one("SELECT * FROM users WHERE username = ? LIMIT 1", [$old]);

        if ($u && cek_password($pass, $u['password'], 'users', 'id_user', (int)$u['id_user'])) {
            if (!in_array($u['role'], ['admin', 'pustakawan'], true)) {
                $error = 'Akun ini bukan akun pustakawan.';
            } elseif ($u['status'] !== 'aktif') {
                $error = 'Akun pustakawan dinonaktifkan.';
            } else {
                login_reset('staf');
                session_regenerate_id(true);
                $_SESSION['staf'] = [
                    'id' => (int)$u['id_user'],
                    'nama' => $u['nama'] ?: $u['username'],
                    'username' => $u['username'],
                    'role' => $u['role'],
                ];
                db_exec("UPDATE users SET last_login = NOW() WHERE id_user = ?", [(int)$u['id_user']]);
                flash('success', 'Selamat bertugas, ' . ($u['nama'] ?: $u['username']) . '!');
                redirect('dashboard_admin.php');
            }
        } else {
            login_gagal('staf');
            $sisaCoba = LOGIN_MAX_GAGAL - ($_SESSION['login_gagal']['staf']['n'] ?? 0);
            $error = 'Username atau password salah.' . ($sisaCoba > 0 ? " Sisa percobaan: $sisaCoba." : ' Login dikunci sementara ' . LOGIN_JEDA_DETIK . ' detik.');
        }
    }
}

$L = [
    'judul' => 'Login Pustakawan',
    'sub' => 'Ruang Kerja Pustakawan',
    'bg' => 'bg-gradient-to-r from-yellow-50 to-yellow-100',
    'ikon' => 'fa-user-tie',
    'label_id' => 'Username',
    'placeholder_id' => 'Username pustakawan',
    'ikon_id' => 'fa-user',
    'gambar' => 'assets/img/read.png',
    'info' => 'Kelola koleksi, anggota, peminjaman, pengembalian dan denda dalam satu tempat.',
    'bantuan' => 'Lupa password? Minta admin perpustakaan me-reset melalui menu <b>Pengaturan &rsaquo; Akun Pustakawan</b>.',
    'error' => $error,
    'kunci' => login_terkunci('staf'),
    'old' => $old,
];
include 'includes/login_view.php';
