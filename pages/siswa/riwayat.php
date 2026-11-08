<?php
$list = db_all("SELECT p.*, b.judul, b.kode_buku, k.tanggal_kembali, k.denda, k.kondisi
                FROM peminjaman p JOIN buku b USING(id_buku) LEFT JOIN pengembalian k USING(id_peminjaman)
                WHERE p.id_anggota = ? ORDER BY p.tanggal_pengajuan DESC", [$id]);
?>
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
  <div>
    <h1 class="text-2xl font-bold text-ink">Riwayat Peminjaman</h1>
    <p class="text-sm text-soft"><?= count($list) ?> transaksi</p>
  </div>
  <div class="relative w-full sm:w-72">
    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-soft"></i>
    <input class="input pl-10" placeholder="Saring riwayat..." data-filter="#tbl-riwayat tbody tr" data-empty="#riwayat-kosong">
  </div>
</div>
<div class="card overflow-x-auto p-2">
  <table class="tbl" id="tbl-riwayat">
    <thead><tr><th>Buku</th><th>Diajukan</th><th>Dipinjam</th><th>Jatuh Tempo</th><th>Dikembalikan</th><th>Denda</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($list as $p): ?>
      <tr>
        <td><p class="font-semibold"><?= e($p['judul']) ?></p><p class="text-xs text-soft"><?= e($p['kode_buku']) ?></p></td>
        <td class="whitespace-nowrap"><?= tgl($p['tanggal_pengajuan']) ?></td>
        <td class="whitespace-nowrap"><?= tgl($p['tanggal_pinjam']) ?></td>
        <td class="whitespace-nowrap"><?= tgl($p['tanggal_jatuh_tempo']) ?></td>
        <td class="whitespace-nowrap"><?= tgl($p['tanggal_kembali']) ?><?= $p['kondisi'] && $p['kondisi'] !== 'baik' ? ' <span class="text-xs text-red-500">(' . e($p['kondisi']) . ')</span>' : '' ?></td>
        <td class="whitespace-nowrap <?= $p['denda'] ? 'font-semibold text-merah' : '' ?>"><?= $p['denda'] ? rupiah($p['denda']) : '-' ?></td>
        <td><?= status_badge($p['status']) ?><?= $p['catatan'] ? '<p class="mt-1 text-[11px] text-soft">' . e($p['catatan']) . '</p>' : '' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <p id="riwayat-kosong" class="<?= $list ? 'hidden' : '' ?> p-8 text-center text-sm text-soft">Belum ada riwayat.</p>
</div>
