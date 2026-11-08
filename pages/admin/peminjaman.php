<?php
$menunggu = db_all("SELECT p.*, a.nama, a.kelas, a.nis, b.judul, b.kode_buku, b.stok,
   (SELECT COUNT(*) FROM peminjaman x WHERE x.id_anggota = p.id_anggota AND x.status IN ('dipinjam','diajukan_kembali')) aktif,
   (SELECT COALESCE(SUM(jumlah),0) FROM denda d WHERE d.id_anggota = p.id_anggota AND d.status = 'belum_dibayar') denda
   FROM peminjaman p JOIN anggota a USING(id_anggota) JOIN buku b USING(id_buku) WHERE p.status = 'menunggu' ORDER BY p.tanggal_pengajuan");
$dipinjam = db_all("SELECT p.*, a.nama, a.kelas, b.judul, b.kode_buku FROM peminjaman p JOIN anggota a USING(id_anggota) JOIN buku b USING(id_buku)
   WHERE p.status IN ('dipinjam','diajukan_kembali') ORDER BY p.tanggal_jatuh_tempo");
$semua = db_all("SELECT p.*, a.nama, a.kelas, b.judul, u.nama petugas FROM peminjaman p JOIN anggota a USING(id_anggota) JOIN buku b USING(id_buku)
   LEFT JOIN users u ON u.id_user = p.diproses_oleh ORDER BY p.id_peminjaman DESC LIMIT 300");
$anggotaList = db_all("SELECT id_anggota, nama, nis, kelas FROM anggota WHERE status = 'aktif' ORDER BY nama");
$bukuList = db_all("SELECT id_buku, judul, kode_buku, stok FROM buku WHERE stok > 0 ORDER BY judul");
$lama = (int)setting('lama_pinjam', 7);
$maks = (int)setting('maks_pinjam', 3);

page_head('Peminjaman Buku', "Lama pinjam $lama hari · maks. $maks buku per siswa");
?>
<div data-tabs>
  <div class="mb-5 flex gap-1 overflow-x-auto rounded-2xl bg-white p-1.5 shadow-soft">
    <button data-tab="menunggu" class="flex-1 whitespace-nowrap rounded-xl px-4 py-2 text-sm font-semibold transition"><i class="fa-solid fa-hourglass-half mr-1"></i> Menunggu <span class="ml-1 rounded-full bg-red-500 px-2 text-xs text-white"><?= count($menunggu) ?></span></button>
    <button data-tab="aktif" class="flex-1 whitespace-nowrap rounded-xl px-4 py-2 text-sm font-semibold transition"><i class="fa-solid fa-book-open-reader mr-1"></i> Sedang Dipinjam (<?= count($dipinjam) ?>)</button>
    <button data-tab="langsung" class="flex-1 whitespace-nowrap rounded-xl px-4 py-2 text-sm font-semibold transition"><i class="fa-solid fa-plus mr-1"></i> Pinjamkan Langsung</button>
    <button data-tab="semua" class="flex-1 whitespace-nowrap rounded-xl px-4 py-2 text-sm font-semibold transition"><i class="fa-solid fa-list mr-1"></i> Semua Transaksi</button>
  </div>

  <!-- MENUNGGU -->
  <div data-tab-panel="menunggu">
    <?php if (!$menunggu): ?>
      <div class="card p-12 text-center"><i class="fa-solid fa-mug-hot mb-3 text-5xl text-gold"></i><p class="font-semibold">Tidak ada pengajuan yang menunggu</p></div>
    <?php endif; ?>
    <div class="grid gap-4 lg:grid-cols-2">
    <?php foreach ($menunggu as $p): $warn = []; if ($p['stok'] < 1) $warn[] = 'Stok habis'; if ($p['aktif'] >= $maks) $warn[] = 'Batas pinjam tercapai'; if ($p['denda']) $warn[] = 'Denda ' . rupiah($p['denda']); ?>
      <div class="card reveal p-5">
        <div class="flex items-start gap-3">
          <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gold/30 font-bold"><?= e(mb_substr($p['nama'], 0, 1)) ?></span>
          <div class="min-w-0 flex-1">
            <p class="font-semibold"><?= e($p['nama']) ?> <span class="text-sm font-normal text-soft">· <?= e($p['kelas']) ?> · <?= e($p['nis']) ?></span></p>
            <p class="text-sm"><i class="fa-solid fa-book mr-1 text-gold-dark"></i><?= e($p['judul']) ?> <span class="text-xs text-soft">(<?= e($p['kode_buku']) ?>, stok <?= (int)$p['stok'] ?>)</span></p>
            <p class="mt-1 text-xs text-soft">Diajukan <?= tgl($p['tanggal_pengajuan'], true) ?> · sedang meminjam <?= (int)$p['aktif'] ?> buku</p>
            <?php if ($warn): ?><p class="mt-2 text-xs font-medium text-red-500"><i class="fa-solid fa-triangle-exclamation"></i> <?= e(implode(' · ', $warn)) ?></p><?php endif; ?>
          </div>
        </div>
        <div class="mt-4 flex justify-end gap-2">
          <button class="btn btn-sm border border-red-200 text-red-500 hover:bg-red-50" data-modal-open="modal-tolak" data-fill='<?= e(json_encode(['id_peminjaman' => $p['id_peminjaman'], 'info' => $p['nama'] . ' — ' . $p['judul']])) ?>'><i class="fa-solid fa-xmark"></i> Tolak</button>
          <form method="post" action="aksi_admin.php">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="pinjam_setujui"><input type="hidden" name="id_peminjaman" value="<?= $p['id_peminjaman'] ?>">
            <button class="btn btn-green btn-sm" <?= $p['stok'] < 1 ? 'disabled' : '' ?>><i class="fa-solid fa-check"></i> Setujui (<?= $lama ?> hari)</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  </div>

  <!-- SEDANG DIPINJAM -->
  <div data-tab-panel="aktif" class="hidden">
    <div class="mb-3 flex justify-end"><?= filter_box('#tbl-aktif tbody tr', 'Cari peminjam / buku...') ?></div>
    <div class="card overflow-x-auto p-2">
      <table class="tbl" id="tbl-aktif">
        <thead><tr><th>Peminjam</th><th>Buku</th><th>Tgl Pinjam</th><th>Jatuh Tempo</th><th>Sisa</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($dipinjam as $p): $sh = sisa_hari($p['tanggal_jatuh_tempo']); ?>
          <tr>
            <td><p class="font-semibold"><?= e($p['nama']) ?></p><p class="text-xs text-soft"><?= e($p['kelas']) ?></p></td>
            <td><?= e($p['judul']) ?><p class="text-xs text-soft"><?= e($p['kode_buku']) ?></p></td>
            <td class="whitespace-nowrap"><?= tgl($p['tanggal_pinjam']) ?></td>
            <td class="whitespace-nowrap"><?= tgl($p['tanggal_jatuh_tempo']) ?><?= $p['jumlah_perpanjang'] ? '<br><span class="text-[11px] text-soft">diperpanjang ' . $p['jumlah_perpanjang'] . 'x</span>' : '' ?></td>
            <td class="whitespace-nowrap font-semibold <?= $sh < 0 ? 'text-merah' : ($sh <= 2 ? 'text-gold-dark' : 'text-hijau') ?>"><?= $sh < 0 ? 'Telat ' . abs($sh) . ' hr' : "$sh hari" ?></td>
            <td><?= status_badge($p['status']) ?></td>
            <td><a href="?page=pengembalian" class="btn btn-outline btn-sm whitespace-nowrap"><i class="fa-solid fa-rotate-left"></i> Kembalikan</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$dipinjam): ?><tr><td colspan="7" class="p-8 text-center text-soft">Tidak ada buku yang sedang dipinjam.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- PINJAMKAN LANGSUNG -->
  <div data-tab-panel="langsung" class="hidden">
    <form method="post" action="aksi_admin.php" class="card mx-auto max-w-2xl space-y-4 p-6">
      <?= csrf_field() ?><input type="hidden" name="aksi" value="pinjam_langsung">
      <div><h3 class="text-lg font-semibold">Catat Peminjaman di Meja Layanan</h3><p class="text-sm text-soft">Untuk siswa yang datang langsung ke perpustakaan. Status langsung "Dipinjam" dan stok berkurang.</p></div>
      <div>
        <label class="label">Anggota *</label>
        <select name="id_anggota" required class="input">
          <option value="">— pilih siswa —</option>
          <?php foreach ($anggotaList as $a): ?><option value="<?= $a['id_anggota'] ?>"><?= e($a['nama'] . ' · ' . $a['kelas'] . ' · ' . $a['nis']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="label">Buku *</label>
        <select name="id_buku" required class="input">
          <option value="">— pilih buku yang tersedia —</option>
          <?php foreach ($bukuList as $b): ?><option value="<?= $b['id_buku'] ?>"><?= e($b['kode_buku'] . ' · ' . $b['judul'] . ' (stok ' . $b['stok'] . ')') ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="label">Tanggal Pinjam</label><input type="date" name="tanggal_pinjam" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" class="input"></div>
        <div><label class="label">Lama Pinjam (hari)</label><input type="number" name="lama" value="<?= $lama ?>" min="1" max="60" class="input"></div>
      </div>
      <button class="btn btn-gold w-full"><i class="fa-solid fa-check"></i> Simpan Peminjaman</button>
    </form>
  </div>

  <!-- SEMUA -->
  <div data-tab-panel="semua" class="hidden">
    <div class="mb-3 flex justify-end"><?= filter_box('#tbl-semua tbody tr', 'Cari transaksi...') ?></div>
    <div class="card overflow-x-auto p-2">
      <table class="tbl" id="tbl-semua">
        <thead><tr><th>#</th><th>Peminjam</th><th>Buku</th><th>Diajukan</th><th>Pinjam → Tempo</th><th>Status</th><th>Petugas</th></tr></thead>
        <tbody>
        <?php foreach ($semua as $p): ?>
          <tr>
            <td class="text-xs text-soft"><?= $p['id_peminjaman'] ?></td>
            <td><p class="font-semibold"><?= e($p['nama']) ?></p><p class="text-xs text-soft"><?= e($p['kelas']) ?></p></td>
            <td><?= e($p['judul']) ?></td>
            <td class="whitespace-nowrap text-xs"><?= tgl($p['tanggal_pengajuan'], true) ?></td>
            <td class="whitespace-nowrap text-xs"><?= tgl($p['tanggal_pinjam']) ?> → <?= tgl($p['tanggal_jatuh_tempo']) ?></td>
            <td><?= status_badge($p['status']) ?><?= $p['catatan'] ? '<p class="mt-1 max-w-[180px] text-[11px] text-soft">' . e($p['catatan']) . '</p>' : '' ?></td>
            <td class="text-xs"><?= e($p['petugas'] ?? '-') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- MODAL TOLAK -->
<div id="modal-tolak" class="modal">
  <form method="post" action="aksi_admin.php" class="modal-box max-w-md p-6">
    <?= csrf_field() ?><input type="hidden" name="aksi" value="pinjam_tolak"><input type="hidden" name="id_peminjaman">
    <h3 class="mb-1 text-lg font-semibold">Tolak Pengajuan</h3>
    <p class="mb-4 text-sm text-soft" data-text="info"></p>
    <label class="label">Alasan penolakan</label>
    <textarea name="catatan" rows="3" class="input" placeholder="Contoh: stok sedang diperbaiki / masih ada denda" maxlength="255"></textarea>
    <div class="mt-5 flex justify-end gap-2">
      <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
      <button class="btn btn-red"><i class="fa-solid fa-xmark"></i> Tolak Pengajuan</button>
    </div>
  </form>
</div>
