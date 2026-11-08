<?php
$q    = trim($_GET['q'] ?? '');
$kat  = trim($_GET['kategori'] ?? '');
$ada  = !empty($_GET['tersedia']);
$urut = $_GET['urut'] ?? 'judul';
$hal  = max(1, (int)($_GET['hal'] ?? 1));
$per  = 18;

$where = ['1=1'];
$par = [];
if ($q !== '') {
    $where[] = '(judul LIKE ? OR penulis LIKE ? OR kode_buku LIKE ? OR penerbit LIKE ?)';
    array_push($par, "%$q%", "%$q%", "%$q%", "%$q%");
}
if ($kat !== '') { $where[] = 'kategori = ?'; $par[] = $kat; }
if ($ada) $where[] = 'stok > 0';
$order = ['judul' => 'judul ASC', 'terbaru' => 'created_at DESC, id_buku DESC', 'tahun' => 'tahun_terbit DESC', 'stok' => 'stok DESC'][$urut] ?? 'judul ASC';
$w = implode(' AND ', $where);

$total = (int)db_value("SELECT COUNT(*) FROM buku WHERE $w", $par);
$jmlHal = max(1, (int)ceil($total / $per));
$hal = min($hal, $jmlHal);
$buku = db_all("SELECT * FROM buku WHERE $w ORDER BY $order LIMIT $per OFFSET " . (($hal - 1) * $per), $par);
$kategori = db_all("SELECT kategori, COUNT(*) n FROM buku GROUP BY kategori ORDER BY kategori");
$qs = fn($ubah) => '?' . http_build_query(array_merge(['page' => 'katalog', 'q' => $q, 'kategori' => $kat, 'tersedia' => $ada ? 1 : null, 'urut' => $urut], $ubah));
?>
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
  <div>
    <h1 class="text-2xl font-bold text-ink">Katalog Buku</h1>
    <p class="text-sm text-soft"><?= $q !== '' ? 'Hasil pencarian "' . e($q) . '" · ' : '' ?><?= $total ?> buku ditemukan</p>
  </div>
  <?php if ($blokir): ?>
    <p class="rounded-xl bg-red-50 px-4 py-2 text-xs font-medium text-red-600"><i class="fa-solid fa-circle-info"></i> Tidak bisa meminjam: <?= e($blokir) ?></p>
  <?php endif; ?>
</div>

<!-- filter -->
<form method="get" class="card mb-4 grid gap-3 p-4 md:grid-cols-[1fr_180px_160px_auto_auto]">
  <input type="hidden" name="page" value="katalog">
  <div class="relative">
    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-soft"></i>
    <input name="q" value="<?= e($q) ?>" placeholder="Judul, penulis, penerbit, kode..." class="input pl-10">
  </div>
  <select name="kategori" class="input" onchange="this.form.submit()">
    <option value="">Semua kategori</option>
    <?php foreach ($kategori as $k): ?><option value="<?= e($k['kategori']) ?>" <?= $kat === $k['kategori'] ? 'selected' : '' ?>><?= e($k['kategori']) ?> (<?= $k['n'] ?>)</option><?php endforeach; ?>
  </select>
  <select name="urut" class="input" onchange="this.form.submit()">
    <?php foreach (['judul' => 'Judul A-Z', 'terbaru' => 'Terbaru', 'tahun' => 'Tahun terbit', 'stok' => 'Stok terbanyak'] as $k => $v): ?>
      <option value="<?= $k ?>" <?= $urut === $k ? 'selected' : '' ?>><?= $v ?></option>
    <?php endforeach; ?>
  </select>
  <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-gray-200 px-3 text-sm">
    <input type="checkbox" name="tersedia" value="1" <?= $ada ? 'checked' : '' ?> onchange="this.form.submit()" class="accent-[#DAA520]"> Tersedia saja
  </label>
  <button class="btn btn-gold"><i class="fa-solid fa-filter"></i> Terapkan</button>
</form>

<!-- chip kategori -->
<div class="mb-6 flex gap-2 overflow-x-auto pb-1">
  <a href="<?= $qs(['kategori' => null, 'hal' => null]) ?>" class="whitespace-nowrap rounded-full px-4 py-1.5 text-xs font-semibold transition <?= $kat === '' ? 'bg-gold text-black shadow-gold' : 'bg-white text-soft hover:bg-gold/30' ?>">Semua</a>
  <?php foreach ($kategori as $k): ?>
    <a href="<?= $qs(['kategori' => $k['kategori'], 'hal' => null]) ?>" class="whitespace-nowrap rounded-full px-4 py-1.5 text-xs font-semibold transition <?= $kat === $k['kategori'] ? 'bg-gold text-black shadow-gold' : 'bg-white text-soft hover:bg-gold/30' ?>"><?= e($k['kategori']) ?></a>
  <?php endforeach; ?>
</div>

<?php if (!$buku): ?>
  <div class="card p-12 text-center">
    <i class="fa-solid fa-book-skull mb-3 text-5xl text-gold"></i>
    <p class="font-semibold">Buku tidak ditemukan</p>
    <p class="text-sm text-soft">Coba kata kunci lain atau hapus filter.</p>
    <a href="?page=katalog" class="btn btn-outline btn-sm mt-4">Reset pencarian</a>
  </div>
<?php else: ?>
  <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
    <?php foreach ($buku as $b) kartu_buku($b, $blokir, $bukuAktif); ?>
  </div>

  <?php if ($jmlHal > 1): ?>
  <nav class="mt-8 flex justify-center gap-1">
    <?php for ($i = 1; $i <= $jmlHal; $i++): ?>
      <a href="<?= $qs(['hal' => $i]) ?>" class="flex h-9 w-9 items-center justify-center rounded-lg text-sm font-semibold <?= $i === $hal ? 'bg-gold-dark text-white' : 'bg-white hover:bg-gold/30' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </nav>
  <?php endif; ?>
<?php endif; ?>
