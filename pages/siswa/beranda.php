<?php
$terbaru = db_all("SELECT * FROM buku ORDER BY created_at DESC, id_buku DESC LIMIT 8");
$populer = db_all("SELECT b.*, COUNT(p.id_peminjaman) AS total FROM buku b
                   JOIN peminjaman p ON p.id_buku = b.id_buku AND p.status IN ('dipinjam','diajukan_kembali','dikembalikan')
                   GROUP BY b.id_buku ORDER BY total DESC, b.judul LIMIT 4");
$segera  = db_all("SELECT p.*, b.judul, b.cover FROM peminjaman p JOIN buku b USING(id_buku)
                   WHERE p.id_anggota = ? AND p.status IN ('dipinjam','diajukan_kembali') ORDER BY p.tanggal_jatuh_tempo LIMIT 3", [$id]);
$totalBuku = (int)db_value("SELECT COUNT(*) FROM buku");
$jam = (int)date('G');
$sapa = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));
?>
<!-- HERO -->
<section class="hero-bg relative mb-8 flex min-h-[15rem] items-center justify-center overflow-hidden rounded-custom p-8 text-center shadow-lg-soft transition hover:shadow-[0_0_40px_rgba(255,215,0,0.5)]">
  <div class="relative z-10 animate-fade-up rounded-3xl bg-white/75 px-6 py-6 shadow-md-soft backdrop-blur-sm sm:px-10">
    <p class="mb-1 text-sm font-medium text-ink/80"><?= $sapa ?> 👋</p>
    <h1 class="text-shine mb-2 text-3xl font-extrabold md:text-4xl">Halo, <?= e($anggota['nama']) ?>!</h1>
    <p class="mb-6 text-ink/90">Temukan petualangan baru di Perpustakaan Digital SMK</p>
    <form method="get" class="mx-auto flex max-w-md overflow-hidden rounded-full bg-white shadow-md-soft">
      <input type="hidden" name="page" value="katalog">
      <input name="q" placeholder="Mau baca apa hari ini?" class="flex-1 bg-transparent px-5 py-3 text-sm focus:outline-none">
      <button class="m-1 rounded-full bg-gold-dark px-5 text-sm font-semibold text-white transition hover:bg-gold-deep"><i class="fa-solid fa-magnifying-glass"></i> Cari</button>
    </form>
  </div>
</section>

<?php if ($hitung['terlambat'] > 0): ?>
<div class="reveal mb-6 flex items-center gap-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
  <i class="fa-solid fa-triangle-exclamation text-2xl"></i>
  <div class="flex-1"><b><?= $hitung['terlambat'] ?> buku terlambat dikembalikan.</b> Denda <?= rupiah(setting('denda_per_hari')) ?>/hari terus bertambah. Segera kembalikan ke perpustakaan.</div>
  <a href="?page=pinjaman" class="btn btn-red btn-sm">Lihat</a>
</div>
<?php endif; ?>

<!-- RINGKASAN -->
<h2 class="mb-4 text-xl font-bold text-ink">Ringkasan Saya</h2>
<div class="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
  <?php foreach ([
      ['Sedang Dipinjam', $hitung['dipinjam'] + $hitung['diajukan_kembali'], 'fa-bookmark', "dari maks. $maks buku", '?page=pinjaman'],
      ['Menunggu Persetujuan', $hitung['menunggu'], 'fa-hourglass-half', 'pengajuan aktif', '?page=pengajuan'],
      ['Buku Selesai Dibaca', $hitung['selesai'], 'fa-book-open-reader', 'sudah dikembalikan', '?page=riwayat'],
      ['Denda Belum Dibayar', $dendaBelum, 'fa-money-bill-wave', $dendaBelum ? 'bayar ke pustakawan' : 'mantap, bebas denda!', '?page=denda'],
  ] as $i => [$lbl, $val, $ic, $sub, $url]): ?>
  <a href="<?= $url ?>" class="card card-hover reveal relative overflow-hidden p-5">
    <i class="fa-solid <?= $ic ?> card-icon absolute right-4 top-4 text-4xl text-gold opacity-40"></i>
    <p class="text-sm text-soft"><?= $lbl ?></p>
    <p class="mt-2 text-3xl font-bold <?= $i === 3 && $val ? 'text-merah' : 'text-ink' ?>" data-count="<?= (int)$val ?>" <?= $i === 3 ? 'data-rupiah' : '' ?>>0</p>
    <p class="mt-1 text-xs text-soft"><?= e($sub) ?></p>
  </a>
  <?php endforeach; ?>
</div>

<?php if ($segera): ?>
<!-- JATUH TEMPO -->
<h2 class="mb-4 text-xl font-bold text-ink">Jatuh Tempo Terdekat</h2>
<div class="mb-8 grid gap-4 md:grid-cols-3">
  <?php foreach ($segera as $p): $sh = sisa_hari($p['tanggal_jatuh_tempo']); $c = cover_url($p['cover']); ?>
  <div class="card reveal flex items-center gap-4 p-4">
    <div class="book-cover w-14 shrink-0 rounded-lg"><?php if ($c): ?><img src="<?= e($c) ?>" alt=""><?php else: ?><i class="fa-solid fa-book text-xl text-gold-dark"></i><?php endif; ?></div>
    <div class="min-w-0 flex-1">
      <p class="truncate text-sm font-semibold"><?= e($p['judul']) ?></p>
      <p class="text-xs text-soft">Tempo: <?= tgl($p['tanggal_jatuh_tempo']) ?></p>
      <p class="mt-1 text-xs font-bold <?= $sh < 0 ? 'text-merah' : ($sh <= 2 ? 'text-gold-dark' : 'text-hijau') ?>">
        <?= $sh < 0 ? 'Terlambat ' . abs($sh) . ' hari' : ($sh === 0 ? 'Hari ini!' : "$sh hari lagi") ?>
      </p>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- BUKU TERBARU -->
<div class="mb-4 flex items-center justify-between">
  <h2 class="text-xl font-bold text-ink">Buku Terbaru</h2>
  <a href="?page=katalog" class="text-sm font-semibold text-gold-dark hover:underline">LIHAT SEMUA (<?= $totalBuku ?>) <i class="fa-solid fa-arrow-right text-xs"></i></a>
</div>
<div class="mb-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
  <?php foreach (array_slice($terbaru, 0, 6) as $b) kartu_buku($b, $blokir, $bukuAktif); ?>
</div>

<?php if ($populer): ?>
<h2 class="mb-4 text-xl font-bold text-ink">🔥 Paling Sering Dipinjam</h2>
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
  <?php foreach ($populer as $i => $b): $c = cover_url($b['cover']); ?>
  <button class="card card-hover reveal flex items-center gap-4 p-4 text-left" data-modal-open="modal-buku" data-book='<?= e(json_encode([
      'id_buku' => $b['id_buku'], 'judul' => $b['judul'], 'penulis' => $b['penulis'], 'penerbit' => $b['penerbit'], 'tahun_terbit' => $b['tahun_terbit'],
      'kode_buku' => $b['kode_buku'], 'kategori' => $b['kategori'], 'rak' => $b['rak'] ?: '-', 'deskripsi' => $b['deskripsi'] ?: 'Belum ada deskripsi.',
      'stok' => (int)$b['stok'], 'stok_label' => $b['stok'] > 0 ? $b['stok'] . ' tersedia' : 'Habis', 'cover' => $c,
      'blok' => in_array($b['id_buku'], $bukuAktif) ? 'Sudah diajukan/dipinjam' : $blokir])) ?>'>
    <span class="text-3xl font-extrabold text-gold">#<?= $i + 1 ?></span>
    <div class="min-w-0"><p class="truncate text-sm font-semibold"><?= e($b['judul']) ?></p><p class="text-xs text-soft"><?= (int)$b['total'] ?>x dipinjam</p></div>
  </button>
  <?php endforeach; ?>
</div>
<?php endif; ?>
