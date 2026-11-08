<?php
$list = db_all("SELECT p.*, b.judul, b.penulis, b.cover, b.kode_buku FROM peminjaman p JOIN buku b USING(id_buku)
                WHERE p.id_anggota = ? AND p.status IN ('dipinjam','diajukan_kembali') ORDER BY p.tanggal_jatuh_tempo", [$id]);
$perHari = (int)setting('denda_per_hari', 1000);
$maksPanjang = (int)setting('maks_perpanjang', 1);
$lama = (int)setting('lama_pinjam', 7);
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold text-ink">Buku yang Sedang Dipinjam</h1>
  <p class="text-sm text-soft"><?= count($list) ?> buku · Lama pinjam <?= $lama ?> hari · Denda <?= rupiah($perHari) ?>/hari keterlambatan</p>
</div>

<?php if (!$list): ?>
  <div class="card p-12 text-center">
    <i class="fa-regular fa-bookmark mb-3 text-5xl text-gold"></i>
    <p class="font-semibold">Anda tidak sedang meminjam buku</p>
    <p class="mb-4 text-sm text-soft">Yuk cari buku menarik di katalog!</p>
    <a href="?page=katalog" class="btn btn-gold btn-sm"><i class="fa-solid fa-book-open"></i> Buka Katalog</a>
  </div>
<?php else: ?>
<div class="grid gap-5 lg:grid-cols-2">
  <?php foreach ($list as $p):
      $sh = sisa_hari($p['tanggal_jatuh_tempo']);
      $total = max(1, (new DateTime($p['tanggal_pinjam']))->diff(new DateTime($p['tanggal_jatuh_tempo']))->days);
      $lewat = (new DateTime($p['tanggal_pinjam']))->diff(new DateTime('today'))->days;
      $persen = min(100, round($lewat / $total * 100));
      $telat = hari_terlambat($p['tanggal_jatuh_tempo']);
      $c = cover_url($p['cover']);
      $bisaPanjang = $p['status'] === 'dipinjam' && $telat === 0 && $p['jumlah_perpanjang'] < $maksPanjang;
  ?>
  <div class="card reveal flex gap-4 p-5 <?= $telat ? 'border-red-300' : '' ?>">
    <div class="book-cover w-24 shrink-0 self-start shadow-soft"><?php if ($c): ?><img src="<?= e($c) ?>" alt=""><?php else: ?><i class="fa-solid fa-book text-3xl text-gold-dark"></i><?php endif; ?></div>
    <div class="min-w-0 flex-1">
      <div class="flex items-start justify-between gap-2">
        <div class="min-w-0">
          <h3 class="truncate font-semibold text-ink"><?= e($p['judul']) ?></h3>
          <p class="text-xs text-soft"><?= e($p['penulis']) ?> · <?= e($p['kode_buku']) ?></p>
        </div>
        <?= status_badge($p['status']) ?>
      </div>

      <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
        <p><span class="text-soft">Dipinjam</span><br><b class="text-hijau"><?= tgl($p['tanggal_pinjam']) ?></b></p>
        <p><span class="text-soft">Jatuh tempo</span><br><b class="text-merah"><?= tgl($p['tanggal_jatuh_tempo']) ?></b></p>
      </div>

      <div class="mt-3">
        <div class="mb-1 flex justify-between text-[11px]">
          <span class="text-soft">Masa pinjam</span>
          <span class="font-bold <?= $sh < 0 ? 'text-merah' : ($sh <= 2 ? 'text-gold-dark' : 'text-hijau') ?>"><?= $sh < 0 ? 'Terlambat ' . abs($sh) . ' hari' : ($sh === 0 ? 'Jatuh tempo hari ini' : "Sisa $sh hari") ?></span>
        </div>
        <div class="h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full <?= $sh < 0 ? 'bg-red-500' : 'bg-gradient-to-r from-gold to-gold-dark' ?>" style="width: <?= $persen ?>%"></div></div>
      </div>

      <?php if ($telat): ?>
        <p class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-600"><i class="fa-solid fa-triangle-exclamation"></i> Perkiraan denda saat ini: <b><?= rupiah($telat * $perHari) ?></b></p>
      <?php endif; ?>

      <div class="mt-4 flex flex-wrap gap-2">
        <?php if ($p['status'] === 'dipinjam'): ?>
          <form method="post" action="aksi_siswa.php" data-confirm="Ajukan pengembalian buku &quot;<?= e($p['judul']) ?>&quot;? Setelah itu serahkan bukunya ke meja pustakawan.">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="ajukan_kembali"><input type="hidden" name="id_peminjaman" value="<?= $p['id_peminjaman'] ?>">
            <button class="btn btn-gold btn-sm"><i class="fa-solid fa-rotate-left"></i> Ajukan Pengembalian</button>
          </form>
          <form method="post" action="aksi_siswa.php" data-confirm="Perpanjang masa pinjam <?= $lama ?> hari lagi?">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="perpanjang"><input type="hidden" name="id_peminjaman" value="<?= $p['id_peminjaman'] ?>">
            <button class="btn btn-outline btn-sm" <?= $bisaPanjang ? '' : 'disabled title="' . ($telat ? 'Tidak bisa diperpanjang karena terlambat' : 'Batas perpanjangan tercapai') . '"' ?>>
              <i class="fa-solid fa-calendar-plus"></i> Perpanjang (<?= $p['jumlah_perpanjang'] ?>/<?= $maksPanjang ?>)
            </button>
          </form>
        <?php else: ?>
          <p class="flex-1 rounded-lg bg-sky-50 px-3 py-2 text-xs text-sky-700"><i class="fa-solid fa-circle-info"></i> Serahkan buku ke pustakawan untuk dikonfirmasi.</p>
          <form method="post" action="aksi_siswa.php">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="batal_kembali"><input type="hidden" name="id_peminjaman" value="<?= $p['id_peminjaman'] ?>">
            <button class="btn btn-ghost btn-sm">Batalkan</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
