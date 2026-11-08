<?php
/**
 * ganti_password.php — wajib ganti password untuk siswa yang masih memakai password bawaan (NIS).
 */
require 'config.php';
$s = require_siswa(true);
$a = db_one("SELECT nama, nis, password, harus_ganti_password FROM anggota WHERE id_anggota = ?", [$s['id']]);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $lama = (string)($_POST['password_lama'] ?? '');
    $baru = (string)($_POST['password_baru'] ?? '');
    $konf = (string)($_POST['konfirmasi'] ?? '');

    if (!password_verify($lama, $a['password'])) {
        $error = 'Password lama salah.';
    } elseif ($err = validasi_password($baru, $konf)) {
        $error = $err;
    } elseif ($baru === $a['nis'] || password_verify($baru, $a['password'])) {
        $error = 'Password baru tidak boleh sama dengan NIS / password lama.';
    } else {
        db_exec("UPDATE anggota SET password = ?, harus_ganti_password = 0 WHERE id_anggota = ?", [hash_password($baru), $s['id']]);
        session_regenerate_id(true);
        flash('success', 'Password berhasil diganti. Selamat datang, ' . $a['nama'] . '!');
        redirect('dashboard_siswa.php');
    }
}

if (!$a['harus_ganti_password'] && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard_siswa.php?page=profil');
}

$title = 'Ganti Password';
include 'includes/head.php';
?>
<body class="flex min-h-screen items-center justify-center bg-gradient-to-r from-blue-50 to-blue-100 p-4">
<div class="w-full max-w-md animate-fade-up rounded-3xl bg-white p-8 shadow-2xl">
  <div class="mb-6 text-center">
    <span class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gold-btn/15 text-3xl text-gold-btn"><i class="fa-solid fa-shield-halved"></i></span>
    <h1 class="text-2xl font-extrabold text-gray-800">Buat Password Baru</h1>
    <p class="mt-1 text-sm text-gray-500">Halo <b><?= e($a['nama']) ?></b>, Anda masih memakai password bawaan (NIS). Ganti dulu agar akun aman.</p>
  </div>

  <?php if ($error): ?>
    <div class="mb-4 rounded-xl border border-red-300 bg-red-50 p-3 text-sm text-red-700"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" class="space-y-4">
    <?= csrf_field() ?>
    <?php include 'includes/password_fields.php'; ?>
    <button class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gold-btn py-3 font-semibold text-white shadow-lg transition hover:bg-gold-btn-dark">
      <i class="fa-solid fa-check"></i> Simpan Password
    </button>
  </form>
  <a href="logout.php" class="mt-4 block text-center text-sm text-gray-500 hover:text-red-500">Batal &amp; logout</a>
</div>
<?php include 'includes/foot.php'; ?>
