<?php
/**
 * login_siswa.php — Login siswa.
 * Alur: NIS / Nomor Anggota + password  ->  (wajib ganti password jika masih default)  ->  dashboard_siswa.php
 */
require 'config.php';

if (siswa()) redirect('dashboard_siswa.php');

$error = '';
$old   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old  = trim($_POST['username'] ?? '');
    $pass = (string)($_POST['password'] ?? '');

    if ($sisa = login_terkunci('siswa')) {
        $error = "Terlalu banyak percobaan gagal. Coba lagi dalam $sisa detik.";
    } elseif ($old === '' || $pass === '') {
        $error = 'NIS / Nomor Anggota dan password wajib diisi.';
    } else {
        $a = db_one("SELECT * FROM anggota WHERE nis = ? OR nomor_anggota = ? LIMIT 1", [$old, $old]);

        if ($a && cek_password($pass, $a['password'], 'anggota', 'id_anggota', (int)$a['id_anggota'])) {
            if ($a['status'] !== 'aktif') {
                $error = 'Akun Anda dinonaktifkan. Silakan hubungi pustakawan.';
            } else {
                login_reset('siswa');
                session_regenerate_id(true);
                $_SESSION['siswa'] = ['id' => (int)$a['id_anggota'], 'nama' => $a['nama'], 'nis' => $a['nis']];

                db_exec("UPDATE anggota SET last_login = NOW() WHERE id_anggota = ?", [(int)$a['id_anggota']]);
                // catat kunjungan (1x per hari)
                $sudah = db_value("SELECT COUNT(*) FROM kunjungan WHERE id_anggota = ? AND DATE(waktu) = CURDATE()", [(int)$a['id_anggota']]);
                if (!$sudah) db_exec("INSERT INTO kunjungan (id_anggota, keperluan) VALUES (?, 'Login aplikasi')", [(int)$a['id_anggota']]);

                if ((int)$a['harus_ganti_password'] === 1) {
                    flash('warning', 'Demi keamanan, silakan ganti password bawaan Anda terlebih dahulu.');
                    redirect('ganti_password.php');
                }
                flash('success', 'Selamat datang kembali, ' . $a['nama'] . '!');
                redirect('dashboard_siswa.php');
            }
        } else {
            login_gagal('siswa');
            $sisaCoba = LOGIN_MAX_GAGAL - ($_SESSION['login_gagal']['siswa']['n'] ?? 0);
            $error = 'NIS / Nomor Anggota atau password salah.' . ($sisaCoba > 0 ? " Sisa percobaan: $sisaCoba." : ' Akun dikunci sementara ' . LOGIN_JEDA_DETIK . ' detik.');
        }
    }
}

$L = [
    'judul' => 'Login Siswa',
    'sub' => 'Ayo Membaca!',
    'bg' => 'bg-gradient-to-r from-blue-50 to-blue-100',
    'ikon' => 'fa-book-open-reader',
    'label_id' => 'NIS / Nomor Anggota',
    'placeholder_id' => 'Contoh: 24011 atau A002',
    'ikon_id' => 'fa-id-card',
    'gambar' => 'assets/img/buku.png',
    'info' => 'Temukan buku favoritmu, ajukan peminjaman, dan pantau jatuh tempo dari mana saja.',
    'bantuan' => 'Password awal siswa adalah <b>NIS</b> masing-masing dan wajib diganti saat login pertama. Lupa password? Minta pustakawan untuk me-reset.',
    'error' => $error,
    'kunci' => login_terkunci('siswa'),
    'old' => $old,
];
include 'includes/login_view.php';
