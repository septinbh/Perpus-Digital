<?php
$u = db_one("SELECT * FROM users WHERE id_user = ?", [$staf['id']]);
$diproses = (int)db_value("SELECT COUNT(*) FROM peminjaman WHERE diproses_oleh = ?", [$staf['id']]);
$dikembalikan = (int)db_value("SELECT COUNT(*) FROM pengembalian WHERE diproses_oleh = ?", [$staf['id']]);
page_head('Profil Saya');
?>
<div class="grid gap-6 lg:grid-cols-[1fr_1.2fr]">
  <div class="space-y-6">
    <div class="card overflow-hidden">
      <div class="h-24 bg-gradient-to-r from-gold to-gold-dark"></div>
      <div class="-mt-12 p-6 pt-0 text-center">
        <img src="assets/img/profil.jpg" class="mx-auto h-24 w-24 rounded-full border-4 border-white object-cover shadow-md-soft" alt="">
        <h2 class="mt-3 text-xl font-bold"><?= e($u['nama']) ?></h2>
        <p class="text-sm text-soft">@<?= e($u['username']) ?> · <?= ucfirst($u['role']) ?></p>
        <div class="mt-5 grid grid-cols-2 divide-x divide-gold/20 text-sm">
          <div><p class="text-xl font-bold"><?= $diproses ?></p><p class="text-xs text-soft">Peminjaman diproses</p></div>
          <div><p class="text-xl font-bold"><?= $dikembalikan ?></p><p class="text-xs text-soft">Pengembalian diproses</p></div>
        </div>
        <p class="mt-4 text-xs text-soft">Login terakhir: <?= tgl($u['last_login'], true) ?></p>
      </div>
    </div>
    <form method="post" action="aksi_admin.php" class="card space-y-3 p-6">
      <?= csrf_field() ?><input type="hidden" name="aksi" value="profil_simpan">
      <h3 class="font-semibold"><i class="fa-solid fa-user-pen mr-1 text-gold-dark"></i> Ubah Nama Tampilan</h3>
      <div class="flex gap-2"><input name="nama" value="<?= e($u['nama']) ?>" required maxlength="100" class="input"><button class="btn btn-gold">Simpan</button></div>
    </form>
  </div>

  <form method="post" action="aksi_admin.php" class="card space-y-4 p-6">
    <?= csrf_field() ?><input type="hidden" name="aksi" value="ganti_password">
    <div><h3 class="text-lg font-semibold"><i class="fa-solid fa-key mr-1 text-gold-dark"></i> Ganti Password</h3>
      <p class="text-sm text-soft">Disarankan mengganti password bawaan (admin123) segera.</p></div>
    <?php include BASE_DIR . '/includes/password_fields.php'; ?>
    <button class="btn btn-gold w-full"><i class="fa-solid fa-floppy-disk"></i> Simpan Password Baru</button>
  </form>
</div>
