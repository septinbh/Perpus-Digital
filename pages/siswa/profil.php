<?php
$sejak = tgl($anggota['created_at']);
$kunjungan = (int)db_value("SELECT COUNT(*) FROM kunjungan WHERE id_anggota = ?", [$id]);
?>
<h1 class="mb-6 text-2xl font-bold text-ink">Profil Anggota</h1>
<div class="grid gap-6 lg:grid-cols-[1fr_1.2fr]">
  <!-- kartu anggota -->
  <div>
    <div class="reveal relative overflow-hidden rounded-custom bg-gradient-to-br from-gold to-gold-dark p-6 text-ink shadow-lg-soft">
      <span class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/20"></span>
      <span class="absolute -bottom-12 -left-6 h-32 w-32 rounded-full bg-white/10"></span>
      <div class="relative flex items-center gap-3">
        <img src="assets/img/logosmk.png" class="h-10 w-10" alt="">
        <div class="text-xs font-semibold leading-tight">KARTU ANGGOTA<br>PERPUSTAKAAN SMKN 1 KAMAL</div>
      </div>
      <div class="relative mt-6 flex items-center gap-4">
        <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-2xl font-bold text-gold-deep shadow"><?= e(mb_strtoupper(mb_substr($anggota['nama'], 0, 1))) ?></span>
        <div>
          <p class="text-xl font-bold"><?= e($anggota['nama']) ?></p>
          <p class="text-sm"><?= e($anggota['kelas']) ?> · <?= e($anggota['jurusan']) ?></p>
        </div>
      </div>
      <div class="relative mt-6 flex justify-between text-sm">
        <div><p class="text-[11px] opacity-70">No. Anggota</p><p class="font-mono font-bold tracking-wider"><?= e($anggota['nomor_anggota']) ?></p></div>
        <div><p class="text-[11px] opacity-70">NIS</p><p class="font-mono font-bold"><?= e($anggota['nis']) ?></p></div>
        <div><p class="text-[11px] opacity-70">Status</p><p class="font-bold"><?= e(ucfirst($anggota['status'])) ?></p></div>
      </div>
    </div>
    <div class="card mt-4 grid grid-cols-3 divide-x divide-gold/20 p-4 text-center text-sm">
      <div><p class="text-xl font-bold"><?= $hitung['selesai'] ?></p><p class="text-xs text-soft">Buku selesai</p></div>
      <div><p class="text-xl font-bold"><?= $kunjungan ?></p><p class="text-xs text-soft">Kunjungan</p></div>
      <div><p class="text-sm font-bold"><?= $sejak ?></p><p class="text-xs text-soft">Anggota sejak</p></div>
    </div>

    <form method="post" action="aksi_siswa.php" class="card mt-4 p-5">
      <?= csrf_field() ?><input type="hidden" name="aksi" value="update_profil">
      <h3 class="mb-3 font-semibold"><i class="fa-solid fa-phone mr-1 text-gold-dark"></i> Kontak</h3>
      <label class="label" for="no_hp">Nomor HP / WhatsApp</label>
      <div class="flex gap-2">
        <input id="no_hp" name="no_hp" value="<?= e($anggota['no_hp']) ?>" class="input" placeholder="08xxxxxxxxxx" pattern="[0-9+ ]{8,20}">
        <button class="btn btn-gold">Simpan</button>
      </div>
      <p class="mt-2 text-xs text-soft">Data lain (nama, kelas, NIS) hanya bisa diubah pustakawan.</p>
    </form>
  </div>

  <!-- ganti password -->
  <form id="password" method="post" action="aksi_siswa.php" class="card reveal space-y-4 p-6">
    <?= csrf_field() ?><input type="hidden" name="aksi" value="ganti_password">
    <div>
      <h3 class="text-lg font-semibold"><i class="fa-solid fa-key mr-1 text-gold-dark"></i> Ganti Password</h3>
      <p class="text-sm text-soft">Gunakan password yang mudah diingat namun sulit ditebak.</p>
    </div>
    <?php include BASE_DIR . '/includes/password_fields.php'; ?>
    <button class="btn btn-gold w-full"><i class="fa-solid fa-floppy-disk"></i> Simpan Password Baru</button>
    <p class="text-xs text-soft">Lupa password lama? Minta pustakawan untuk me-reset; password akan kembali ke NIS dan Anda wajib menggantinya saat login.</p>
  </form>
</div>
