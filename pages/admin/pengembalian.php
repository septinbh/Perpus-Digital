<?php
$aktif = db_all("SELECT p.*, a.nama, a.kelas, a.nis, b.judul, b.kode_buku FROM peminjaman p JOIN anggota a USING(id_anggota) JOIN buku b USING(id_buku)
   WHERE p.status IN ('dipinjam','diajukan_kembali') ORDER BY p.status = 'diajukan_kembali' DESC, p.tanggal_jatuh_tempo");
$riwayat = db_all("SELECT k.*, p.tanggal_pinjam, p.tanggal_jatuh_tempo, a.nama, a.kelas, b.judul, u.nama petugas
   FROM pengembalian k JOIN peminjaman p USING(id_peminjaman) JOIN anggota a USING(id_anggota) JOIN buku b USING(id_buku)
   LEFT JOIN users u ON u.id_user = k.diproses_oleh ORDER BY k.id_pengembalian DESC LIMIT 300");
$perHari = (int)setting('denda_per_hari', 1000);
$rusak = (int)setting('denda_rusak', 0);
$hilang = (int)setting('denda_hilang', 0);
$telat = array_filter($aktif, fn($p) => hari_terlambat($p['tanggal_jatuh_tempo']) > 0);

page_head('Pengembalian Buku', 'Denda ' . rupiah($perHari) . '/hari · rusak ' . rupiah($rusak) . ' · hilang ' . rupiah($hilang));

/** Baris tabel peminjaman aktif. */
$baris = function (array $p) use ($perHari) {
    $h = hari_terlambat($p['tanggal_jatuh_tempo']);
    $fill = ['id_peminjaman' => $p['id_peminjaman'], 'info_nama' => $p['nama'] . ' (' . $p['kelas'] . ')', 'info_buku' => $p['judul'],
        'info_pinjam' => tgl($p['tanggal_pinjam']), 'info_tempo' => tgl($p['tanggal_jatuh_tempo']), 'jatuh_tempo' => $p['tanggal_jatuh_tempo'],
        'tanggal_kembali' => date('Y-m-d'), 'kondisi' => 'baik'];
    ?>
    <tr class="<?= $p['status'] === 'diajukan_kembali' ? 'bg-sky-50/60' : '' ?>">
      <td><p class="font-semibold"><?= e($p['nama']) ?></p><p class="text-xs text-soft"><?= e($p['kelas']) ?> · <?= e($p['nis']) ?></p></td>
      <td><?= e($p['judul']) ?><p class="text-xs text-soft"><?= e($p['kode_buku']) ?></p></td>
      <td class="whitespace-nowrap"><?= tgl($p['tanggal_pinjam']) ?></td>
      <td class="whitespace-nowrap"><?= tgl($p['tanggal_jatuh_tempo']) ?></td>
      <td class="whitespace-nowrap <?= $h ? 'font-semibold text-merah' : 'text-soft' ?>"><?= $h ? "$h hari · " . rupiah($h * $perHari) : 'Tepat waktu' ?></td>
      <td><?= status_badge($p['status']) ?></td>
      <td class="text-right"><button class="btn btn-gold btn-sm whitespace-nowrap" data-modal-open="modal-kembali" data-fill='<?= e(json_encode($fill)) ?>'><i class="fa-solid fa-rotate-left"></i> Proses</button></td>
    </tr>
    <?php
};
?>
<div data-tabs>
  <div class="mb-5 flex gap-1 overflow-x-auto rounded-2xl bg-white p-1.5 shadow-soft">
    <button data-tab="proses" class="flex-1 whitespace-nowrap rounded-xl px-4 py-2 text-sm font-semibold transition"><i class="fa-solid fa-inbox mr-1"></i> Perlu Diproses (<?= count($aktif) ?>)</button>
    <button data-tab="terlambat" class="flex-1 whitespace-nowrap rounded-xl px-4 py-2 text-sm font-semibold transition"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Terlambat (<?= count($telat) ?>)</button>
    <button data-tab="riwayat" class="flex-1 whitespace-nowrap rounded-xl px-4 py-2 text-sm font-semibold transition"><i class="fa-solid fa-clock-rotate-left mr-1"></i> Riwayat</button>
  </div>

  <?php foreach (['proses' => $aktif, 'terlambat' => $telat] as $tab => $rows): ?>
  <div data-tab-panel="<?= $tab ?>" class="hidden">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
      <p class="text-xs text-soft"><?= $tab === 'proses' ? '<span class="inline-block h-3 w-3 rounded bg-sky-100 align-middle"></span> Baris biru = siswa sudah mengajukan pengembalian lewat aplikasi.' : 'Peminjaman yang sudah melewati jatuh tempo.' ?></p>
      <?= filter_box("#tbl-$tab tbody tr", 'Cari peminjam / buku...') ?>
    </div>
    <div class="card overflow-x-auto p-2">
      <table class="tbl" id="tbl-<?= $tab ?>">
        <thead><tr><th>Peminjam</th><th>Buku</th><th>Dipinjam</th><th>Jatuh Tempo</th><th>Keterlambatan</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($rows as $p) $baris($p); ?>
          <?php if (!$rows): ?><tr><td colspan="7" class="p-8 text-center text-soft">Tidak ada data.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>

  <div data-tab-panel="riwayat" class="hidden">
    <div class="mb-3 flex justify-end"><?= filter_box('#tbl-riwayat tbody tr', 'Cari riwayat...') ?></div>
    <div class="card overflow-x-auto p-2">
      <table class="tbl" id="tbl-riwayat">
        <thead><tr><th>Peminjam</th><th>Buku</th><th>Tempo</th><th>Dikembalikan</th><th>Kondisi</th><th>Denda</th><th>Petugas</th></tr></thead>
        <tbody>
        <?php foreach ($riwayat as $r): ?>
          <tr>
            <td><p class="font-semibold"><?= e($r['nama']) ?></p><p class="text-xs text-soft"><?= e($r['kelas']) ?></p></td>
            <td><?= e($r['judul']) ?></td>
            <td class="whitespace-nowrap"><?= tgl($r['tanggal_jatuh_tempo']) ?></td>
            <td class="whitespace-nowrap"><?= tgl($r['tanggal_kembali']) ?><?= $r['terlambat_hari'] ? '<br><span class="text-xs text-merah">telat ' . $r['terlambat_hari'] . ' hari</span>' : '' ?></td>
            <td><span class="rounded-full px-2 py-0.5 text-xs font-semibold <?= ['baik' => 'bg-emerald-100 text-emerald-700', 'rusak' => 'bg-amber-100 text-amber-700', 'hilang' => 'bg-red-100 text-red-600'][$r['kondisi']] ?>"><?= ucfirst($r['kondisi']) ?></span></td>
            <td class="<?= $r['denda'] ? 'font-semibold text-merah' : 'text-soft' ?>"><?= $r['denda'] ? rupiah($r['denda']) : '-' ?></td>
            <td class="text-xs"><?= e($r['petugas'] ?? '-') ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$riwayat): ?><tr><td colspan="7" class="p-8 text-center text-soft">Belum ada pengembalian.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- MODAL PROSES PENGEMBALIAN -->
<div id="modal-kembali" class="modal">
  <form method="post" action="aksi_admin.php" class="modal-box" data-denda-form data-per-hari="<?= $perHari ?>" data-rusak="<?= $rusak ?>" data-hilang="<?= $hilang ?>">
    <?= csrf_field() ?><input type="hidden" name="aksi" value="kembali_proses"><input type="hidden" name="id_peminjaman"><input type="hidden" name="jatuh_tempo">
    <div class="flex items-center justify-between border-b border-gold/20 px-6 py-4">
      <h3 class="text-lg font-semibold">Proses Pengembalian</h3>
      <button type="button" class="text-2xl text-soft hover:text-ink" data-modal-close>&times;</button>
    </div>
    <div class="space-y-4 p-6">
      <div class="rounded-xl bg-krem/70 p-4 text-sm">
        <p class="font-semibold" data-text="info_buku"></p>
        <p class="text-soft" data-text="info_nama"></p>
        <p class="mt-2 text-xs text-soft">Dipinjam <b data-text="info_pinjam"></b> · Jatuh tempo <b class="text-merah" data-text="info_tempo"></b></p>
      </div>
      <div><label class="label">Tanggal Dikembalikan</label><input type="date" name="tanggal_kembali" max="<?= date('Y-m-d') ?>" required class="input"></div>
      <div>
        <label class="label">Kondisi Buku</label>
        <div class="grid grid-cols-3 gap-2">
          <?php foreach (['baik' => ['fa-circle-check', 'text-hijau'], 'rusak' => ['fa-book-skull', 'text-amber-600'], 'hilang' => ['fa-circle-question', 'text-merah']] as $k => [$ic, $cl]): ?>
          <label class="cursor-pointer">
            <input type="radio" name="kondisi" value="<?= $k ?>" class="peer sr-only" <?= $k === 'baik' ? 'checked' : '' ?>>
            <span class="flex flex-col items-center gap-1 rounded-xl border-2 border-gray-200 p-3 text-sm font-medium transition peer-checked:border-gold-dark peer-checked:bg-gold/10"><i class="fa-solid <?= $ic ?> <?= $cl ?> text-xl"></i><?= ucfirst($k) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="flex items-center justify-between rounded-xl border border-gold-dark/30 bg-gold/10 p-4">
        <div><p class="text-xs text-soft">Total denda</p><p class="text-[11px] text-soft" data-denda-detail></p></div>
        <p class="text-2xl font-bold text-merah" data-denda-total>Rp 0</p>
      </div>
    </div>
    <div class="flex justify-end gap-2 border-t border-gold/20 px-6 py-4">
      <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
      <button class="btn btn-gold"><i class="fa-solid fa-check"></i> Konfirmasi Pengembalian</button>
    </div>
  </form>
</div>
