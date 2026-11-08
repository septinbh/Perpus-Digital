<?php
$q   = trim($_GET['q'] ?? '');
$kat = trim($_GET['kategori'] ?? '');
$stokF = $_GET['stok'] ?? '';
$where = ['1=1']; $par = [];
if ($q !== '') { $where[] = '(b.judul LIKE ? OR b.penulis LIKE ? OR b.kode_buku LIKE ? OR b.penerbit LIKE ?)'; array_push($par, "%$q%", "%$q%", "%$q%", "%$q%"); }
if ($kat !== '') { $where[] = 'b.kategori = ?'; $par[] = $kat; }
if ($stokF === 'habis') $where[] = 'b.stok = 0';
$buku = db_all("SELECT b.*, (SELECT COUNT(*) FROM peminjaman p WHERE p.id_buku = b.id_buku AND p.status IN ('dipinjam','diajukan_kembali')) dipinjam
                FROM buku b WHERE " . implode(' AND ', $where) . " ORDER BY b.id_buku DESC", $par);
$kategori = array_column(db_all("SELECT DISTINCT kategori FROM buku ORDER BY kategori"), 'kategori');
$kosong = ['id_buku' => '', 'kode_buku' => '', 'judul' => '', 'penulis' => '', 'penerbit' => '', 'tahun_terbit' => '', 'kategori' => 'Umum', 'rak' => '', 'stok' => 1, 'deskripsi' => '', 'cover' => null, 'cover_preview' => null, 'judul_modal' => 'Tambah Buku'];
// usulan kode buku berikutnya
$lastKode = db_value("SELECT kode_buku FROM buku WHERE kode_buku REGEXP '^SMK[0-9]+$' ORDER BY CAST(SUBSTRING(kode_buku,4) AS UNSIGNED) DESC LIMIT 1");
$kosong['kode_buku'] = 'SMK' . str_pad((string)((int)substr($lastKode ?? 'SMK0', 3) + 1), 3, '0', STR_PAD_LEFT);

page_head('Kelola Data Buku', count($buku) . ' judul' . ($q !== '' ? ' · pencarian "' . e($q) . '"' : ''),
    '<button class="btn btn-gold" data-modal-open="modal-buku-form" data-fill=\'' . e(json_encode($kosong)) . '\'><i class="fa-solid fa-plus"></i> Tambah Buku</button>');
?>
<form method="get" class="card mb-4 flex flex-wrap items-center gap-3 p-4">
  <input type="hidden" name="page" value="buku">
  <div class="relative min-w-[220px] flex-1">
    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-soft"></i>
    <input name="q" value="<?= e($q) ?>" class="input pl-10" placeholder="Judul, penulis, penerbit, kode..." data-filter="#tbl-buku tbody tr">
  </div>
  <select name="kategori" class="input w-auto" onchange="this.form.submit()">
    <option value="">Semua kategori</option>
    <?php foreach ($kategori as $k): ?><option <?= $k === $kat ? 'selected' : '' ?>><?= e($k) ?></option><?php endforeach; ?>
  </select>
  <select name="stok" class="input w-auto" onchange="this.form.submit()">
    <option value="">Semua stok</option><option value="habis" <?= $stokF === 'habis' ? 'selected' : '' ?>>Stok habis</option>
  </select>
  <?php if ($q !== '' || $kat !== '' || $stokF !== ''): ?><a href="?page=buku" class="btn btn-ghost btn-sm">Reset</a><?php endif; ?>
</form>

<div class="card overflow-x-auto p-2">
  <table class="tbl" id="tbl-buku">
    <thead><tr><th>Buku</th><th>Kode</th><th>Kategori</th><th>Penerbit / Tahun</th><th class="text-center">Tersedia</th><th class="text-center">Dipinjam</th><th class="text-right">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($buku as $b): $c = cover_url($b['cover']); $fill = $b + ['judul_modal' => 'Edit Buku', 'cover_preview' => $c]; ?>
      <tr data-search="<?= e($b['judul'] . ' ' . $b['penulis'] . ' ' . $b['kode_buku'] . ' ' . $b['penerbit'] . ' ' . $b['kategori']) ?>">
        <td>
          <div class="flex items-center gap-3">
            <div class="book-cover w-10 shrink-0 rounded-md"><?php if ($c): ?><img src="<?= e($c) ?>" alt=""><?php else: ?><i class="fa-solid fa-book text-gold-dark"></i><?php endif; ?></div>
            <div class="min-w-0"><p class="max-w-[260px] truncate font-semibold"><?= e($b['judul']) ?></p><p class="text-xs text-soft"><?= e($b['penulis'] ?: '-') ?></p></div>
          </div>
        </td>
        <td class="font-mono text-xs"><?= e($b['kode_buku']) ?><?= $b['rak'] ? '<br><span class="text-soft">Rak ' . e($b['rak']) . '</span>' : '' ?></td>
        <td><span class="rounded-md bg-gold/15 px-2 py-0.5 text-xs font-medium text-gold-deep"><?= e($b['kategori']) ?></span></td>
        <td class="text-xs"><?= e($b['penerbit'] ?: '-') ?><br><span class="text-soft"><?= e($b['tahun_terbit'] ?: '-') ?></span></td>
        <td class="text-center"><span class="font-bold <?= $b['stok'] > 0 ? 'text-hijau' : 'text-merah' ?>"><?= (int)$b['stok'] ?></span></td>
        <td class="text-center"><?= (int)$b['dipinjam'] ?></td>
        <td class="whitespace-nowrap text-right">
          <button class="btn btn-sm bg-blue-500 text-white hover:bg-blue-600" data-modal-open="modal-buku-form" data-fill='<?= e(json_encode($fill)) ?>'><i class="fa-solid fa-pen"></i> Edit</button>
          <form method="post" action="aksi_admin.php" class="inline" data-confirm="Hapus buku &quot;<?= e($b['judul']) ?>&quot;? Tindakan ini tidak bisa dibatalkan." data-danger data-ok="Hapus">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="buku_hapus"><input type="hidden" name="id_buku" value="<?= $b['id_buku'] ?>">
            <button class="btn btn-red btn-sm"><i class="fa-solid fa-trash"></i></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$buku): ?><tr><td colspan="7" class="p-10 text-center text-soft">Belum ada data buku.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<!-- MODAL TAMBAH / EDIT BUKU -->
<div id="modal-buku-form" class="modal">
  <form method="post" action="aksi_admin.php" enctype="multipart/form-data" class="modal-box max-w-3xl">
    <?= csrf_field() ?><input type="hidden" name="aksi" value="buku_simpan"><input type="hidden" name="id_buku">
    <div class="flex items-center justify-between border-b border-gold/20 px-6 py-4">
      <h3 class="text-lg font-semibold" data-text="judul_modal">Tambah Buku</h3>
      <button type="button" class="text-2xl text-soft hover:text-ink" data-modal-close>&times;</button>
    </div>
    <div class="grid gap-6 p-6 md:grid-cols-[170px_1fr]">
      <div>
        <div class="book-cover mb-3 w-full shadow-soft">
          <img id="cover-preview" data-src="cover_preview" class="absolute inset-0 hidden" alt="">
          <i class="fa-solid fa-image text-4xl text-gold-dark/50"></i>
        </div>
        <label class="btn btn-outline btn-sm w-full cursor-pointer"><i class="fa-solid fa-upload"></i> Pilih Cover
          <input type="file" name="cover_file" accept="image/jpeg,image/png,image/webp" class="hidden" data-preview="#cover-preview">
        </label>
        <p class="mt-2 text-center text-[11px] text-soft">JPG/PNG/WEBP, maks 2 MB</p>
        <label class="mt-2 flex items-center justify-center gap-2 text-xs text-soft"><input type="checkbox" name="hapus_cover" value="1" data-reset class="accent-[#EF4444]"> Hapus cover</label>
      </div>
      <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2"><label class="label">Judul Buku *</label><input name="judul" required maxlength="200" class="input"></div>
        <div><label class="label">Kode Buku *</label><input name="kode_buku" required maxlength="50" class="input font-mono"></div>
        <div><label class="label">Kategori *</label>
          <input name="kategori" required list="list-kategori" maxlength="50" class="input">
          <datalist id="list-kategori"><?php foreach ($kategori as $k): ?><option value="<?= e($k) ?>"><?php endforeach; ?></datalist>
        </div>
        <div><label class="label">Penulis</label><input name="penulis" maxlength="150" class="input"></div>
        <div><label class="label">Penerbit</label><input name="penerbit" maxlength="150" class="input"></div>
        <div><label class="label">Tahun Terbit</label><input name="tahun_terbit" type="number" min="1901" max="<?= date('Y') ?>" class="input"></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="label">Stok *</label><input name="stok" type="number" min="0" required class="input"></div>
          <div><label class="label">Rak</label><input name="rak" maxlength="20" class="input" placeholder="A1"></div>
        </div>
        <div class="sm:col-span-2"><label class="label">Deskripsi / Sinopsis</label><textarea name="deskripsi" rows="3" class="input"></textarea></div>
      </div>
    </div>
    <div class="flex justify-end gap-2 border-t border-gold/20 px-6 py-4">
      <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
      <button class="btn btn-gold"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
    </div>
  </form>
</div>
