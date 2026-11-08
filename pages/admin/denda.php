<?php
$f = $_GET['status'] ?? 'belum_dibayar';
if (!in_array($f, ['belum_dibayar', 'lunas', 'semua'], true)) $f = 'belum_dibayar';
$list = db_all("SELECT d.*, a.nama, a.kelas, a.nis, b.judul FROM denda d JOIN anggota a USING(id_anggota)
   JOIN peminjaman p USING(id_peminjaman) JOIN buku b ON b.id_buku = p.id_buku "
   . ($f !== 'semua' ? "WHERE d.status = ?" : '') . " ORDER BY d.created_at DESC", $f !== 'semua' ? [$f] : []);
$sum = db_one("SELECT COALESCE(SUM(CASE WHEN status='belum_dibayar' THEN jumlah END),0) belum,
                      COALESCE(SUM(CASE WHEN status='lunas' THEN jumlah END),0) lunas,
                      COALESCE(SUM(CASE WHEN status='lunas' AND DATE(tanggal_bayar)=CURDATE() THEN jumlah END),0) hari_ini FROM denda");
page_head('Denda', 'Kelola tagihan denda keterlambatan, buku rusak, dan hilang');
?>
<div class="mb-6 grid gap-4 sm:grid-cols-3">
  <div class="card card-hover p-5"><p class="text-sm text-soft">Belum dibayar</p><p class="mt-1 text-2xl font-bold text-merah" data-count="<?= $sum['belum'] ?>" data-rupiah>Rp 0</p></div>
  <div class="card card-hover p-5"><p class="text-sm text-soft">Total sudah lunas</p><p class="mt-1 text-2xl font-bold text-hijau" data-count="<?= $sum['lunas'] ?>" data-rupiah>Rp 0</p></div>
  <div class="card card-hover p-5"><p class="text-sm text-soft">Diterima hari ini</p><p class="mt-1 text-2xl font-bold text-ink" data-count="<?= $sum['hari_ini'] ?>" data-rupiah>Rp 0</p></div>
</div>

<div class="card mb-4 flex flex-wrap items-center justify-between gap-3 p-4">
  <div class="flex gap-1 rounded-xl bg-krem p-1 text-sm">
    <?php foreach (['belum_dibayar' => 'Belum Dibayar', 'lunas' => 'Lunas', 'semua' => 'Semua'] as $k => $v): ?>
      <a href="?page=denda&status=<?= $k ?>" class="rounded-lg px-3 py-1.5 font-medium <?= $f === $k ? 'bg-gold text-black shadow-gold' : 'text-soft hover:text-ink' ?>"><?= $v ?></a>
    <?php endforeach; ?>
  </div>
  <?= filter_box('#tbl-denda tbody tr', 'Cari nama / buku...') ?>
</div>

<div class="card overflow-x-auto p-2">
  <table class="tbl" id="tbl-denda">
    <thead><tr><th>Anggota</th><th>Buku</th><th>Keterangan</th><th>Jumlah</th><th>Status</th><th>Dibuat / Dibayar</th><th class="text-right">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($list as $d): ?>
      <tr>
        <td><p class="font-semibold"><?= e($d['nama']) ?></p><p class="text-xs text-soft"><?= e($d['kelas']) ?> · <?= e($d['nis']) ?></p></td>
        <td><?= e($d['judul']) ?></td>
        <td class="text-xs text-soft"><?= e($d['keterangan']) ?></td>
        <td class="font-semibold"><?= rupiah($d['jumlah']) ?></td>
        <td><?= status_badge($d['status']) ?></td>
        <td class="whitespace-nowrap text-xs"><?= tgl($d['created_at']) ?><br><span class="text-soft"><?= $d['tanggal_bayar'] ? 'Bayar ' . tgl($d['tanggal_bayar'], true) : '' ?></span></td>
        <td class="whitespace-nowrap text-right">
          <?php if ($d['status'] === 'belum_dibayar'): ?>
          <form method="post" action="aksi_admin.php" class="inline" data-confirm="Tandai denda <?= rupiah($d['jumlah']) ?> atas nama <?= e($d['nama']) ?> sebagai LUNAS?" data-ok="Ya, lunas">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="denda_lunas"><input type="hidden" name="id_denda" value="<?= $d['id_denda'] ?>">
            <button class="btn btn-green btn-sm"><i class="fa-solid fa-money-bill-wave"></i> Bayar</button>
          </form>
          <?php else: ?>
          <a href="cetak_struk.php?id=<?= $d['id_denda'] ?>" target="_blank" class="btn btn-outline btn-sm"><i class="fa-solid fa-print"></i> Struk</a>
          <?php endif; ?>
          <?php if ($staf['role'] === 'admin'): ?>
          <form method="post" action="aksi_admin.php" class="inline" data-confirm="Hapus/putihkan denda ini?" data-danger data-ok="Hapus">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="denda_hapus"><input type="hidden" name="id_denda" value="<?= $d['id_denda'] ?>">
            <button class="btn btn-ghost btn-sm text-red-500" title="Hapus (khusus admin)"><i class="fa-solid fa-trash"></i></button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$list): ?><tr><td colspan="7" class="p-10 text-center text-soft">Tidak ada data denda.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
