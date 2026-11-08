<?php
$st = db_one("SELECT
   (SELECT COUNT(*) FROM buku) judul,
   (SELECT COALESCE(SUM(stok),0) FROM buku) tersedia,
   (SELECT COUNT(*) FROM peminjaman WHERE status IN ('dipinjam','diajukan_kembali')) dipinjam,
   (SELECT COUNT(*) FROM kunjungan WHERE DATE(waktu) = CURDATE()) pengunjung,
   (SELECT COALESCE(SUM(jumlah),0) FROM denda WHERE status = 'belum_dibayar') denda,
   (SELECT COUNT(*) FROM anggota WHERE status = 'aktif') anggota");

// grafik 7 hari terakhir
$raw = db_all("SELECT DATE(tanggal_pinjam) d, COUNT(*) n FROM peminjaman WHERE tanggal_pinjam >= CURDATE() - INTERVAL 6 DAY GROUP BY d");
$rawK = db_all("SELECT tanggal_kembali d, COUNT(*) n FROM pengembalian WHERE tanggal_kembali >= CURDATE() - INTERVAL 6 DAY GROUP BY d");
$mapP = array_column($raw, 'n', 'd'); $mapK = array_column($rawK, 'n', 'd');
$hari = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
$chart = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $chart[] = ['l' => $hari[date('w', strtotime($d))], 'p' => (int)($mapP[$d] ?? 0), 'k' => (int)($mapK[$d] ?? 0)];
}
$maxC = max(1, max(array_map(fn($c) => max($c['p'], $c['k']), $chart)));

$menunggu = db_all("SELECT p.id_peminjaman, p.tanggal_pengajuan, a.nama, a.kelas, b.judul, b.stok FROM peminjaman p
                    JOIN anggota a USING(id_anggota) JOIN buku b USING(id_buku) WHERE p.status = 'menunggu' ORDER BY p.tanggal_pengajuan LIMIT 5");
$telat = db_all("SELECT p.id_peminjaman, p.tanggal_jatuh_tempo, a.nama, a.kelas, b.judul FROM peminjaman p
                 JOIN anggota a USING(id_anggota) JOIN buku b USING(id_buku)
                 WHERE p.status IN ('dipinjam','diajukan_kembali') AND p.tanggal_jatuh_tempo < CURDATE() ORDER BY p.tanggal_jatuh_tempo LIMIT 5");
$aktivitas = db_all("SELECT p.status, p.tanggal_pengajuan, p.tanggal_pinjam, a.nama, b.judul,
                     COALESCE(k.created_at, p.tanggal_pinjam, p.tanggal_pengajuan) AS waktu
                     FROM peminjaman p JOIN anggota a USING(id_anggota) JOIN buku b USING(id_buku)
                     LEFT JOIN pengembalian k USING(id_peminjaman) ORDER BY waktu DESC, p.id_peminjaman DESC LIMIT 6");
$kategori = db_all("SELECT kategori, COUNT(*) n FROM buku GROUP BY kategori ORDER BY n DESC LIMIT 6");
$totalKat = max(1, array_sum(array_column($kategori, 'n')));
$perHari = (int)setting('denda_per_hari');
?>
<!-- SAPAAN -->
<section class="relative mb-6 flex items-center justify-between overflow-hidden rounded-custom bg-gradient-to-br from-gold to-gold-dark p-6 text-ink shadow-md-soft">
  <span class="absolute -right-10 -top-16 h-48 w-48 rounded-full bg-white/15"></span>
  <div class="relative z-10 animate-fade-up">
    <h1 class="text-2xl font-bold sm:text-3xl">Hai, <?= e($staf['nama']) ?> 👋</h1>
    <p class="mt-1 text-sm sm:text-base">Selamat datang di Dashboard Perpustakaan Digital, siap melayani hari ini!</p>
    <div class="mt-4 flex flex-wrap gap-2">
      <a href="?page=peminjaman#langsung" class="btn btn-sm bg-white/90 text-ink hover:bg-white"><i class="fa-solid fa-plus"></i> Peminjaman Baru</a>
      <a href="?page=pengembalian" class="btn btn-sm bg-white/30 text-ink hover:bg-white/60"><i class="fa-solid fa-rotate-left"></i> Proses Pengembalian</a>
    </div>
  </div>
  <div class="relative hidden pr-4 md:block">
    <i class="fa-solid fa-book-open animate-floaty text-7xl text-white/80"></i>
  </div>
</section>

<!-- STATISTIK -->
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
  <?php foreach ([
      ['Total Buku Tersedia', $st['tersedia'], 'fa-book-reader', $st['judul'] . ' judul koleksi', '?page=buku', ''],
      ['Buku Dipinjam', $st['dipinjam'], 'fa-bookmark', $badge['terlambat'] . ' terlambat', '?page=pengembalian', ''],
      ['Pengunjung Hari Ini', $st['pengunjung'], 'fa-user-check', $st['anggota'] . ' anggota aktif', '?page=pengunjung', ''],
      ['Denda Belum Dibayar', $st['denda'], 'fa-exclamation-triangle', $badge['denda'] . ' tagihan', '?page=denda', 'rp'],
  ] as [$l, $v, $ic, $sub, $url, $rp]): ?>
  <a href="<?= $url ?>" class="card card-hover reveal flex items-center justify-between p-5">
    <div>
      <p class="text-sm text-soft"><?= $l ?></p>
      <p class="mt-1 text-2xl font-bold sm:text-3xl <?= $rp && $v ? 'text-merah' : 'text-ink' ?>" data-count="<?= (int)$v ?>" <?= $rp ? 'data-rupiah' : '' ?>>0</p>
      <p class="mt-1 text-xs text-soft"><?= e($sub) ?></p>
    </div>
    <i class="fa-solid <?= $ic ?> card-icon text-4xl text-gold-dark sm:text-5xl"></i>
  </a>
  <?php endforeach; ?>
</div>

<div class="mb-6 grid gap-6 xl:grid-cols-3">
  <!-- GRAFIK -->
  <div class="card reveal p-5 xl:col-span-2">
    <div class="mb-4 flex items-center justify-between">
      <h2 class="font-semibold">Aktivitas 7 Hari Terakhir</h2>
      <div class="flex gap-4 text-xs text-soft">
        <span><i class="fa-solid fa-square text-gold-dark"></i> Dipinjam</span>
        <span><i class="fa-solid fa-square text-hijau"></i> Dikembalikan</span>
      </div>
    </div>
    <div class="flex h-48 items-end gap-3 border-b border-gold/30 pb-1">
      <?php foreach ($chart as $c): ?>
      <div class="flex h-full flex-1 items-end justify-center gap-1" title="<?= $c['l'] ?>: <?= $c['p'] ?> dipinjam, <?= $c['k'] ?> kembali">
        <div class="bar relative w-1/2 max-w-[22px]" style="height:0" data-bar="<?= max(2, round($c['p'] / $maxC * 100)) ?>"><span class="absolute -top-5 left-1/2 -translate-x-1/2 text-[10px] font-semibold"><?= $c['p'] ?: '' ?></span></div>
        <div class="relative w-1/2 max-w-[22px] rounded-t-lg bg-hijau/80 transition-all duration-700" style="height:0" data-bar="<?= max(2, round($c['k'] / $maxC * 100)) ?>"><span class="absolute -top-5 left-1/2 -translate-x-1/2 text-[10px] font-semibold"><?= $c['k'] ?: '' ?></span></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="mt-2 flex gap-3 text-center text-xs text-soft"><?php foreach ($chart as $c): ?><span class="flex-1"><?= $c['l'] ?></span><?php endforeach; ?></div>
  </div>

  <!-- KATEGORI -->
  <div class="card reveal p-5">
    <h2 class="mb-4 font-semibold">Koleksi per Kategori</h2>
    <div class="space-y-3">
      <?php foreach ($kategori as $k): $pc = round($k['n'] / $totalKat * 100); ?>
      <div>
        <div class="mb-1 flex justify-between text-xs"><span><?= e($k['kategori']) ?></span><span class="text-soft"><?= $k['n'] ?> judul</span></div>
        <div class="h-2 overflow-hidden rounded-full bg-krem"><div class="h-full rounded-full bg-gradient-to-r from-gold to-gold-dark" style="width:<?= $pc ?>%"></div></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="grid gap-6 xl:grid-cols-2">
  <!-- PENGAJUAN MENUNGGU -->
  <div class="card reveal p-5">
    <div class="mb-4 flex items-center justify-between">
      <h2 class="font-semibold"><i class="fa-solid fa-hourglass-half mr-1 text-gold-dark"></i> Pengajuan Menunggu</h2>
      <a href="?page=peminjaman" class="text-xs font-semibold text-gold-dark hover:underline">Semua</a>
    </div>
    <?php if (!$menunggu): ?><p class="py-6 text-center text-sm text-soft">Tidak ada pengajuan baru 🎉</p><?php endif; ?>
    <div class="divide-y divide-gold/20">
    <?php foreach ($menunggu as $m): ?>
      <div class="flex items-center gap-3 py-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gold/30 text-sm font-bold"><?= e(mb_substr($m['nama'], 0, 1)) ?></span>
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-semibold"><?= e($m['nama']) ?> <span class="font-normal text-soft">· <?= e($m['kelas']) ?></span></p>
          <p class="truncate text-xs text-soft"><?= e($m['judul']) ?> · stok <?= (int)$m['stok'] ?></p>
        </div>
        <form method="post" action="aksi_admin.php" class="flex gap-1">
          <?= csrf_field() ?><input type="hidden" name="id_peminjaman" value="<?= $m['id_peminjaman'] ?>">
          <button name="aksi" value="pinjam_setujui" class="btn btn-green btn-sm" title="Setujui" <?= $m['stok'] < 1 ? 'disabled' : '' ?>><i class="fa-solid fa-check"></i></button>
          <button name="aksi" value="pinjam_tolak" class="btn btn-sm border border-red-200 text-red-500 hover:bg-red-50" title="Tolak"><i class="fa-solid fa-xmark"></i></button>
        </form>
      </div>
    <?php endforeach; ?>
    </div>
  </div>

  <!-- TERLAMBAT -->
  <div class="card reveal p-5">
    <div class="mb-4 flex items-center justify-between">
      <h2 class="font-semibold"><i class="fa-solid fa-triangle-exclamation mr-1 text-merah"></i> Terlambat Dikembalikan</h2>
      <a href="?page=pengembalian#terlambat" class="text-xs font-semibold text-gold-dark hover:underline">Semua</a>
    </div>
    <?php if (!$telat): ?><p class="py-6 text-center text-sm text-soft">Semua peminjaman tepat waktu 👍</p><?php endif; ?>
    <div class="divide-y divide-gold/20">
    <?php foreach ($telat as $t): $h = hari_terlambat($t['tanggal_jatuh_tempo']); ?>
      <div class="flex items-center gap-3 py-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100 text-xs font-bold text-red-600"><?= $h ?>h</span>
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-semibold"><?= e($t['nama']) ?> <span class="font-normal text-soft">· <?= e($t['kelas']) ?></span></p>
          <p class="truncate text-xs text-soft"><?= e($t['judul']) ?> · tempo <?= tgl($t['tanggal_jatuh_tempo']) ?></p>
        </div>
        <span class="text-sm font-semibold text-merah"><?= rupiah($h * $perHari) ?></span>
      </div>
    <?php endforeach; ?>
    </div>
  </div>

  <!-- AKTIVITAS -->
  <div class="card reveal p-5 xl:col-span-2">
    <h2 class="mb-4 font-semibold"><i class="fa-solid fa-clock-rotate-left mr-1 text-gold-dark"></i> Aktivitas Terbaru</h2>
    <ol class="relative ml-3 border-l-2 border-gold/40">
      <?php foreach ($aktivitas as $a): ?>
      <li class="mb-4 ml-5">
        <span class="absolute -left-[9px] mt-1 h-4 w-4 rounded-full border-2 border-white bg-gold-dark"></span>
        <p class="text-sm"><b><?= e($a['nama']) ?></b> — <?= e($a['judul']) ?> <?= status_badge($a['status']) ?></p>
        <p class="text-xs text-soft"><?= tgl($a['waktu'], strlen($a['waktu']) > 10) ?></p>
      </li>
      <?php endforeach; ?>
    </ol>
  </div>
</div>
