<?php
require 'config.php';
$title = 'Pilih Masuk';
$s = siswa();
$p = staf();
include 'includes/head.php';
?>
<body class="flex min-h-screen items-center justify-center bg-gray-100 px-4 py-10">
<div class="w-full max-w-3xl animate-fade-up text-center">

  <div class="mb-5 inline-flex h-20 w-20 animate-floaty items-center justify-center rounded-3xl bg-white shadow-lg-soft">
    <svg width="54" height="54" viewBox="0 0 24 24" fill="none" stroke="#E3B341" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M2 4.5C2 3.119 3.119 2 4.5 2H12v16H4.5A2.5 2.5 0 0 1 2 15.5v-11z"/><path d="M22 4.5C22 3.119 20.881 2 19.5 2H12v16h7.5A2.5 2.5 0 0 0 22 15.5v-11z"/>
    </svg>
  </div>
  <h1 class="mb-1 text-3xl font-bold text-gray-800">Perpus Digital</h1>
  <p class="mb-10 text-gray-600">SMKN 1 Kamal &middot; Pilih cara masuk</p>

  <?php if ($s || $p): ?>
  <div class="mx-auto mb-8 max-w-xl rounded-2xl border border-gold-btn/40 bg-white p-4 text-sm shadow-soft">
    <p class="mb-3 text-gray-600"><i class="fa-solid fa-circle-check mr-1 text-hijau"></i> Anda masih login:</p>
    <div class="flex flex-wrap justify-center gap-2">
      <?php if ($s): ?><a href="dashboard_siswa.php" class="btn btn-gold btn-sm"><i class="fa-solid fa-user-graduate"></i> Lanjut sebagai <?= e($s['nama']) ?></a><?php endif; ?>
      <?php if ($p): ?><a href="dashboard_admin.php" class="btn btn-gold btn-sm"><i class="fa-solid fa-user-tie"></i> Lanjut sebagai <?= e($p['nama']) ?></a><?php endif; ?>
      <a href="logout.php" class="btn btn-ghost btn-sm">Logout</a>
    </div>
  </div>
  <?php endif; ?>

  <div class="grid gap-6 sm:grid-cols-2">
    <a href="login_siswa.php" class="group card card-hover relative overflow-hidden p-7 text-left">
      <span class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-gold-btn/15 transition group-hover:scale-150"></span>
      <div class="relative">
        <span class="card-icon mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gold-btn text-2xl text-white shadow-md-soft"><i class="fa-solid fa-user-graduate"></i></span>
        <h2 class="text-xl font-bold text-gray-800">Login Siswa</h2>
        <p class="mt-1 text-sm text-gray-500">Cari buku, ajukan peminjaman, cek jatuh tempo &amp; denda.</p>
        <p class="mt-4 text-xs text-gray-400"><i class="fa-solid fa-id-card mr-1"></i> Masuk dengan NIS / Nomor Anggota</p>
        <span class="mt-5 inline-flex items-center gap-2 font-semibold text-gold-btn-dark">Masuk <i class="fa-solid fa-arrow-right transition group-hover:translate-x-1"></i></span>
      </div>
    </a>
    <a href="login_admin.php" class="group card card-hover relative overflow-hidden p-7 text-left">
      <span class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-gold-btn/15 transition group-hover:scale-150"></span>
      <div class="relative">
        <span class="card-icon mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl border-2 border-gold-btn bg-white text-2xl text-gold-btn shadow-md-soft"><i class="fa-solid fa-user-tie"></i></span>
        <h2 class="text-xl font-bold text-gray-800">Login Pustakawan</h2>
        <p class="mt-1 text-sm text-gray-500">Kelola buku, anggota, peminjaman, pengembalian &amp; denda.</p>
        <p class="mt-4 text-xs text-gray-400"><i class="fa-solid fa-key mr-1"></i> Masuk dengan username pustakawan</p>
        <span class="mt-5 inline-flex items-center gap-2 font-semibold text-gold-btn-dark">Masuk <i class="fa-solid fa-arrow-right transition group-hover:translate-x-1"></i></span>
      </div>
    </a>
  </div>

  <p class="mt-10 text-xs text-gray-500">© <?= date('Y') ?> Perpus Digital SMKN 1 Kamal</p>
</div>
<?php include 'includes/foot.php'; ?>
