<?php
/**
 * includes/login_view.php — tampilan form login (dipakai login_siswa & login_admin).
 * Butuh $L = [judul, sub, bg, ikon, label_id, placeholder_id, ikon_id, gambar, info, error, kunci, old]
 */
$title = $L['judul'];
include BASE_DIR . '/includes/head.php';
?>
<body class="flex min-h-screen items-center justify-center <?= $L['bg'] ?> p-4">

<a href="role.php" class="absolute left-4 top-4 flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm text-gray-600 shadow transition hover:bg-gray-200">
  <i class="fa-solid fa-arrow-left"></i> Kembali
</a>

<div class="grid w-full max-w-4xl animate-fade-up overflow-hidden rounded-3xl bg-white shadow-2xl md:grid-cols-2">

  <!-- ilustrasi -->
  <div class="relative hidden flex-col items-center justify-center bg-gradient-to-br from-[#FFF6D5] to-[#F5F5DC] p-10 md:flex">
    <span class="absolute left-6 top-6 h-16 w-16 rounded-full bg-gold-btn/20"></span>
    <span class="absolute bottom-10 right-8 h-24 w-24 rounded-full bg-gold-btn/20"></span>
    <img src="<?= e($L['gambar']) ?>" alt="" class="relative w-72 animate-floaty drop-shadow-xl">
    <h3 class="relative mt-6 text-xl font-bold text-gray-800"><?= e($L['sub']) ?></h3>
    <p class="relative mt-2 text-center text-sm text-gray-500"><?= e($L['info']) ?></p>
  </div>

  <!-- form -->
  <div class="p-8 sm:p-10">
    <div class="mb-6 text-center">
      <span class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gold-btn/15 text-3xl text-gold-btn"><i class="fa-solid <?= e($L['ikon']) ?>"></i></span>
      <h2 class="text-3xl font-extrabold text-gray-800"><?= e($L['judul']) ?></h2>
      <p class="mt-1 text-sm text-gray-500">Perpustakaan Digital SMKN 1 Kamal</p>
    </div>

    <?php if (!empty($L['error'])): ?>
      <div class="mb-4 flex items-start gap-2 rounded-xl border border-red-300 bg-red-50 p-3 text-sm text-red-700">
        <i class="fa-solid fa-circle-exclamation mt-0.5"></i><span><?= e($L['error']) ?></span>
      </div>
    <?php endif; ?>

    <form method="post" class="space-y-5" autocomplete="on">
      <?= csrf_field() ?>
      <div>
        <label class="label" for="username"><?= e($L['label_id']) ?></label>
        <div class="relative">
          <i class="fa-solid <?= e($L['ikon_id']) ?> absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
          <input id="username" type="text" name="username" required autofocus value="<?= e($L['old']) ?>"
                 placeholder="<?= e($L['placeholder_id']) ?>" autocomplete="username"
                 class="w-full rounded-xl border border-gray-300 p-3 pl-11 text-gray-700 transition focus:border-gold-btn focus:outline-none focus:ring-2 focus:ring-gold-btn/50">
        </div>
      </div>
      <div>
        <label class="label" for="password">Password</label>
        <div class="relative">
          <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
          <input id="password" type="password" name="password" required placeholder="Masukkan password" autocomplete="current-password"
                 class="w-full rounded-xl border border-gray-300 p-3 pl-11 pr-12 text-gray-700 transition focus:border-gold-btn focus:outline-none focus:ring-2 focus:ring-gold-btn/50">
          <button type="button" data-toggle-password="password" class="absolute right-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="Tampilkan password"><i class="fa-regular fa-eye"></i></button>
        </div>
        <p data-capslock="password" class="mt-1.5 hidden text-xs font-medium text-amber-600"><i class="fa-solid fa-triangle-exclamation"></i> Caps Lock aktif</p>
      </div>

      <button type="submit" name="login" value="1" <?= $L['kunci'] ? 'disabled' : '' ?> id="btn-login"
              class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gold-btn py-3 font-semibold text-white shadow-lg transition hover:scale-[1.02] hover:bg-gold-btn-dark disabled:cursor-not-allowed disabled:opacity-60">
        <i class="fa-solid fa-right-to-bracket"></i> <span><?= $L['kunci'] ? 'Coba lagi dalam ' . (int)$L['kunci'] . ' detik' : 'Masuk' ?></span>
      </button>
    </form>

    <div class="mt-6 rounded-xl bg-gray-50 p-3 text-xs leading-relaxed text-gray-500">
      <i class="fa-solid fa-circle-info mr-1 text-gold-btn"></i><?= $L['bantuan'] ?>
    </div>
    <p class="mt-5 text-center text-xs text-gray-500">© <?= date('Y') ?> Perpustakaan Digital SMKN 1 Kamal</p>
  </div>
</div>

<?php if ($L['kunci']): ?>
<script>
  (function () {
    let s = <?= (int)$L['kunci'] ?>; const b = document.getElementById('btn-login'), t = b.querySelector('span');
    const iv = setInterval(() => { s--; if (s <= 0) { clearInterval(iv); b.disabled = false; t.textContent = 'Masuk'; } else t.textContent = 'Coba lagi dalam ' + s + ' detik'; }, 1000);
  })();
</script>
<?php endif; ?>
<?php include BASE_DIR . '/includes/foot.php'; ?>
