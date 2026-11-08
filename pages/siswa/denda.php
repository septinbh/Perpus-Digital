<?php
$list = db_all("SELECT d.*, b.judul, p.tanggal_jatuh_tempo FROM denda d JOIN peminjaman p USING(id_peminjaman) JOIN buku b USING(id_buku)
                WHERE d.id_anggota = ? ORDER BY d.status, d.created_at DESC", [$id]);
$lunas = array_sum(array_map(fn($d) => $d['status'] === 'lunas' ? $d['jumlah'] : 0, $list));
?>
<h1 class="mb-6 text-2xl font-bold text-ink">Denda Saya</h1>
<div class="mb-6 grid gap-4 sm:grid-cols-3">
  <div class="card p-5"><p class="text-sm text-soft">Belum dibayar</p><p class="mt-1 text-2xl font-bold text-merah" data-count="<?= $dendaBelum ?>" data-rupiah>Rp 0</p></div>
  <div class="card p-5"><p class="text-sm text-soft">Sudah lunas</p><p class="mt-1 text-2xl font-bold text-hijau" data-count="<?= $lunas ?>" data-rupiah>Rp 0</p></div>
  <div class="card p-5"><p class="text-sm text-soft">Tarif keterlambatan</p><p class="mt-1 text-2xl font-bold text-ink"><?= rupiah(setting('denda_per_hari')) ?><span class="text-sm font-normal text-soft">/hari</span></p></div>
</div>
<?php if ($dendaBelum && setting('blokir_jika_denda') === '1'): ?>
  <p class="mb-4 rounded-xl border border-gold-dark/40 bg-gold/10 p-4 text-sm"><i class="fa-solid fa-circle-info text-gold-dark"></i> Selama masih ada denda, Anda belum bisa mengajukan peminjaman baru. Pembayaran dilakukan langsung di meja pustakawan.</p>
<?php endif; ?>
<div class="card overflow-x-auto p-2">
  <table class="tbl">
    <thead><tr><th>Buku</th><th>Keterangan</th><th>Jumlah</th><th>Status</th><th>Tanggal Bayar</th></tr></thead>
    <tbody>
      <?php foreach ($list as $d): ?>
      <tr>
        <td class="font-semibold"><?= e($d['judul']) ?></td>
        <td class="text-soft"><?= e($d['keterangan']) ?></td>
        <td class="font-semibold"><?= rupiah($d['jumlah']) ?></td>
        <td><?= status_badge($d['status']) ?></td>
        <td><?= tgl($d['tanggal_bayar'], true) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$list): ?><tr><td colspan="5" class="p-10 text-center text-soft"><i class="fa-solid fa-face-smile-beam mb-2 block text-4xl text-gold"></i>Anda tidak punya denda. Pertahankan!</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
