<?php
$set = db_all("SELECT * FROM pengaturan ORDER BY FIELD(kunci,'lama_pinjam','maks_pinjam','maks_perpanjang','denda_per_hari','denda_rusak','denda_hilang','blokir_jika_denda')");
$isAdmin = $staf['role'] === 'admin';
$stafList = $isAdmin ? db_all("SELECT id_user, username, nama, role, status, last_login FROM users ORDER BY role, nama") : [];
page_head('Pengaturan', 'Aturan peminjaman, denda, dan akun pustakawan');
?>
<div class="grid gap-6 xl:grid-cols-2">
  <form method="post" action="aksi_admin.php" class="card space-y-4 p-6">
    <?= csrf_field() ?><input type="hidden" name="aksi" value="pengaturan_simpan">
    <h2 class="font-semibold"><i class="fa-solid fa-sliders mr-1 text-gold-dark"></i> Aturan Perpustakaan</h2>
    <?php foreach ($set as $s): ?>
      <div class="flex items-center justify-between gap-4 border-b border-gold/15 pb-3">
        <label class="text-sm" for="set-<?= e($s['kunci']) ?>"><?= e($s['label']) ?></label>
        <?php if ($s['kunci'] === 'blokir_jika_denda'): ?>
          <select id="set-<?= e($s['kunci']) ?>" name="set[<?= e($s['kunci']) ?>]" class="input w-36"><option value="1" <?= $s['nilai'] === '1' ? 'selected' : '' ?>>Ya</option><option value="0" <?= $s['nilai'] === '0' ? 'selected' : '' ?>>Tidak</option></select>
        <?php else: ?>
          <input id="set-<?= e($s['kunci']) ?>" type="number" min="0" name="set[<?= e($s['kunci']) ?>]" value="<?= e($s['nilai']) ?>" class="input w-36 text-right" <?= $isAdmin ? '' : 'readonly' ?>>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <?php if ($isAdmin): ?><button class="btn btn-gold w-full"><i class="fa-solid fa-floppy-disk"></i> Simpan Pengaturan</button>
    <?php else: ?><p class="text-xs text-soft"><i class="fa-solid fa-lock"></i> Hanya akun dengan peran <b>admin</b> yang dapat mengubah pengaturan.</p><?php endif; ?>
  </form>

  <?php if ($isAdmin): ?>
  <div class="card p-6">
    <div class="mb-4 flex items-center justify-between">
      <h2 class="font-semibold"><i class="fa-solid fa-user-shield mr-1 text-gold-dark"></i> Akun Pustakawan</h2>
      <button class="btn btn-gold btn-sm" data-modal-open="modal-staf" data-fill='<?= e(json_encode(['id_user' => '', 'username' => '', 'nama' => '', 'role' => 'pustakawan', 'judul_modal' => 'Tambah Pustakawan'])) ?>'><i class="fa-solid fa-plus"></i> Tambah</button>
    </div>
    <div class="divide-y divide-gold/20">
    <?php foreach ($stafList as $u): ?>
      <div class="flex flex-wrap items-center gap-3 py-3">
        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gold/30 font-bold"><?= e(mb_strtoupper(mb_substr($u['nama'] ?: $u['username'], 0, 1))) ?></span>
        <div class="min-w-0 flex-1">
          <p class="font-semibold"><?= e($u['nama']) ?> <span class="text-xs font-normal text-soft">@<?= e($u['username']) ?></span></p>
          <p class="text-xs text-soft"><?= ucfirst($u['role']) ?> · login <?= tgl($u['last_login'], true) ?></p>
        </div>
        <?= status_badge($u['status']) ?>
        <div class="flex gap-1">
          <button class="btn btn-ghost btn-sm" title="Edit" data-modal-open="modal-staf" data-fill='<?= e(json_encode($u + ['judul_modal' => 'Edit Pustakawan'])) ?>'><i class="fa-solid fa-pen"></i></button>
          <?php if ($u['id_user'] !== $staf['id']): ?>
          <form method="post" action="aksi_admin.php"><?= csrf_field() ?><input type="hidden" name="aksi" value="staf_status"><input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
            <button class="btn btn-ghost btn-sm" title="<?= $u['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>"><i class="fa-solid <?= $u['status'] === 'aktif' ? 'fa-user-slash' : 'fa-user-check' ?>"></i></button></form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php if ($isAdmin): ?>
<div id="modal-staf" class="modal">
  <form method="post" action="aksi_admin.php" class="modal-box max-w-md p-6">
    <?= csrf_field() ?><input type="hidden" name="aksi" value="staf_simpan"><input type="hidden" name="id_user">
    <h3 class="mb-4 text-lg font-semibold" data-text="judul_modal">Tambah Pustakawan</h3>
    <div class="space-y-4">
      <div><label class="label">Nama *</label><input name="nama" required maxlength="100" class="input"></div>
      <div><label class="label">Username *</label><input name="username" required maxlength="50" pattern="[a-zA-Z0-9_.]{3,50}" class="input" title="3-50 karakter: huruf, angka, titik, garis bawah"></div>
      <div><label class="label">Peran</label><select name="role" class="input"><option value="pustakawan">Pustakawan</option><option value="admin">Admin</option></select></div>
      <div>
        <label class="label">Password <span class="font-normal text-soft">(kosongkan jika tidak diubah)</span></label>
        <div class="relative"><input id="pw-staf" name="password" type="password" minlength="6" class="input pr-11" autocomplete="new-password">
          <button type="button" data-toggle-password="pw-staf" class="absolute right-1.5 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100"><i class="fa-regular fa-eye"></i></button></div>
        <p class="mt-1 text-xs text-soft">Wajib diisi untuk akun baru. Minimal 6 karakter, huruf &amp; angka.</p>
      </div>
    </div>
    <div class="mt-6 flex justify-end gap-2"><button type="button" class="btn btn-ghost" data-modal-close>Batal</button><button class="btn btn-gold">Simpan</button></div>
  </form>
</div>
<?php endif; ?>
