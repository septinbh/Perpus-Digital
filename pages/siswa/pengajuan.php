<?php
$list = db_all("SELECT p.*, b.judul, b.penulis, b.cover, b.stok FROM peminjaman p JOIN buku b USING(id_buku)
                WHERE p.id_anggota = ? AND p.status = 'menunggu' ORDER BY p.tanggal_pengajuan DESC", [$id]);
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold text-ink">Daftar Tunggu</h1>
  <p class="text-sm text-soft">Pengajuan peminjaman yang menunggu persetujuan pustakawan.</p>
</div>

<!-- alur -->
<div class="card mb-6 grid gap-4 p-5 text-center text-xs sm:grid-cols-4">
  <?php foreach ([['fa-magnifying-glass', 'Pilih buku di katalog'], ['fa-paper-plane', 'Ajukan peminjaman'], ['fa-user-check', 'Pustakawan menyetujui'], ['fa-book-open-reader', 'Ambil buku & baca']] as $i => [$ic, $t]): ?>
    <div class="relative">
      <span class="mx-auto mb-2 flex h-11 w-11 items-center justify-center rounded-full <?= $i <= 1 ? 'bg-gold text-black' : 'bg-krem text-gold-dark' ?>"><i class="fa-solid <?= $ic ?>"></i></span>
      <p class="font-semibold text-ink"><?= $i + 1 ?>. <?= $t ?></p>
    </div>
  <?php endforeach; ?>
</div>

<?php if (!$list): ?>
  <div class="card p-12 text-center">
    <i class="fa-solid fa-hourglass-start mb-3 text-5xl text-gold"></i>
    <p class="font-semibold">Tidak ada pengajuan yang menunggu</p>
    <a href="?page=katalog" class="btn btn-gold btn-sm mt-4"><i class="fa-solid fa-plus"></i> Ajukan Peminjaman</a>
  </div>
<?php else: ?>
<div class="space-y-3">
  <?php foreach ($list as $p): $c = cover_url($p['cover']); ?>
  <div class="card reveal flex flex-wrap items-center gap-4 p-4">
    <div class="book-cover w-14 shrink-0 rounded-lg"><?php if ($c): ?><img src="<?= e($c) ?>" alt=""><?php else: ?><i class="fa-solid fa-book text-xl text-gold-dark"></i><?php endif; ?></div>
    <div class="min-w-0 flex-1">
      <p class="font-semibold"><?= e($p['judul']) ?></p>
      <p class="text-xs text-soft"><?= e($p['penulis']) ?> · Diajukan <?= tgl($p['tanggal_pengajuan'], true) ?></p>
    </div>
    <?= status_badge('menunggu') ?>
    <form method="post" action="aksi_siswa.php" data-confirm="Batalkan pengajuan buku ini?" data-danger data-ok="Ya, batalkan">
      <?= csrf_field() ?><input type="hidden" name="aksi" value="batal"><input type="hidden" name="id_peminjaman" value="<?= $p['id_peminjaman'] ?>">
      <button class="btn btn-sm border border-red-200 text-red-500 hover:bg-red-50"><i class="fa-solid fa-xmark"></i> Batalkan</button>
    </form>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
