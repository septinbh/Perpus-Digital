<?php
$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
foreach (['dari', 'sampai'] as $v) if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $$v)) $$v = date('Y-m-d');
$r = db_one("SELECT
  (SELECT COUNT(*) FROM peminjaman WHERE tanggal_pinjam BETWEEN ? AND ?) pinjam,
  (SELECT COUNT(*) FROM pengembalian WHERE tanggal_kembali BETWEEN ? AND ?) kembali,
  (SELECT COUNT(*) FROM pengembalian WHERE tanggal_kembali BETWEEN ? AND ? AND terlambat_hari > 0) telat,
  (SELECT COALESCE(SUM(jumlah),0) FROM denda WHERE status='lunas' AND DATE(tanggal_bayar) BETWEEN ? AND ?) denda_masuk,
  (SELECT COUNT(*) FROM kunjungan WHERE DATE(waktu) BETWEEN ? AND ?) kunjungan,
  (SELECT COUNT(*) FROM peminjaman WHERE status='ditolak' AND DATE(tanggal_pengajuan) BETWEEN ? AND ?) ditolak",
  [$dari, $sampai, $dari, $sampai, $dari, $sampai, $dari, $sampai, $dari, $sampai, $dari, $sampai]);
$topBuku = db_all("SELECT b.judul, b.kategori, COUNT(*) n FROM peminjaman p JOIN buku b USING(id_buku) WHERE p.tanggal_pinjam BETWEEN ? AND ? GROUP BY b.id_buku ORDER BY n DESC LIMIT 10", [$dari, $sampai]);
$topSiswa = db_all("SELECT a.nama, a.kelas, COUNT(*) n FROM peminjaman p JOIN anggota a USING(id_anggota) WHERE p.tanggal_pinjam BETWEEN ? AND ? GROUP BY a.id_anggota ORDER BY n DESC LIMIT 10", [$dari, $sampai]);
page_head('Laporan Perpustakaan', 'Periode ' . tgl($dari) . ' s/d ' . tgl($sampai),
    '<button onclick="window.print()" class="btn btn-outline no-print"><i class="fa-solid fa-print"></i> Cetak</button>');
?>
<form method="get" class="card no-print mb-6 flex flex-wrap items-end gap-3 p-4">
  <input type="hidden" name="page" value="laporan">
  <div><label class="label">Dari</label><input type="date" name="dari" value="<?= e($dari) ?>" class="input"></div>
  <div><label class="label">Sampai</label><input type="date" name="sampai" value="<?= e($sampai) ?>" class="input"></div>
  <button class="btn btn-gold"><i class="fa-solid fa-filter"></i> Tampilkan</button>
  <a href="?page=laporan&dari=<?= date('Y-m-d', strtotime('-6 day')) ?>&sampai=<?= date('Y-m-d') ?>" class="btn btn-ghost btn-sm">7 hari</a>
  <a href="?page=laporan&dari=<?= date('Y-01-01') ?>&sampai=<?= date('Y-m-d') ?>" class="btn btn-ghost btn-sm">Tahun ini</a>
</form>
<div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-6">
  <?php foreach ([['Peminjaman', $r['pinjam'], 'fa-handshake'], ['Pengembalian', $r['kembali'], 'fa-rotate-left'], ['Kembali Terlambat', $r['telat'], 'fa-clock'],
                  ['Pengajuan Ditolak', $r['ditolak'], 'fa-xmark'], ['Kunjungan', $r['kunjungan'], 'fa-users'], ['Denda Masuk', $r['denda_masuk'], 'fa-money-bill-wave']] as $i => [$l, $v, $ic]): ?>
  <div class="card p-4"><i class="fa-solid <?= $ic ?> text-gold-dark"></i><p class="mt-2 text-xs text-soft"><?= $l ?></p><p class="text-xl font-bold" data-count="<?= (int)$v ?>" <?= $i === 5 ? 'data-rupiah' : '' ?>>0</p></div>
  <?php endforeach; ?>
</div>
<div class="grid gap-6 lg:grid-cols-2">
  <?php foreach ([['Buku Terpopuler', $topBuku, 'judul', 'kategori'], ['Siswa Paling Rajin Meminjam', $topSiswa, 'nama', 'kelas']] as [$jd, $rows, $k1, $k2]): ?>
  <div class="card p-5">
    <h2 class="mb-3 font-semibold"><?= $jd ?></h2>
    <table class="tbl"><thead><tr><th>#</th><th><?= $k1 === 'judul' ? 'Judul' : 'Nama' ?></th><th class="text-right">Jumlah</th></tr></thead><tbody>
      <?php foreach ($rows as $i => $x): ?><tr><td><?= $i + 1 ?></td><td><?= e($x[$k1]) ?> <span class="text-xs text-soft">· <?= e($x[$k2]) ?></span></td><td class="text-right font-semibold"><?= $x['n'] ?>x</td></tr><?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="3" class="p-6 text-center text-soft">Tidak ada data pada periode ini.</td></tr><?php endif; ?>
    </tbody></table>
  </div>
  <?php endforeach; ?>
</div>
