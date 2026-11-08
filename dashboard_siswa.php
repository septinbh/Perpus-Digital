<?php
/**
 * dashboard_siswa.php — halaman utama siswa (router ?page=...).
 */
require 'config.php';
$s  = require_siswa();
$id = $s['id'];

$anggota = db_one("SELECT * FROM anggota WHERE id_anggota = ?", [$id]);
$_SESSION['siswa']['nama'] = $anggota['nama'];

// ---------- data ringkas yang dipakai banyak halaman ----------
$hitung = db_one("SELECT
      SUM(status = 'menunggu')          AS menunggu,
      SUM(status = 'dipinjam')          AS dipinjam,
      SUM(status = 'diajukan_kembali')  AS diajukan_kembali,
      SUM(status = 'dikembalikan')      AS selesai,
      SUM(status = 'dipinjam' AND tanggal_jatuh_tempo < CURDATE()) AS terlambat
    FROM peminjaman WHERE id_anggota = ?", [$id]);
$hitung = array_map('intval', $hitung);
$dendaBelum = (int)db_value("SELECT COALESCE(SUM(jumlah),0) FROM denda WHERE id_anggota = ? AND status = 'belum_dibayar'", [$id]);
$aktif = $hitung['menunggu'] + $hitung['dipinjam'] + $hitung['diajukan_kembali'];

// alasan siswa tidak bisa meminjam (null = boleh)
$maks = (int)setting('maks_pinjam', 3);
$blokir = null;
if ($aktif >= $maks) $blokir = "Batas $maks buku tercapai";
elseif ($dendaBelum > 0 && setting('blokir_jika_denda', '1') === '1') $blokir = 'Lunasi denda dulu';

// buku yang sedang diajukan/dipinjam siswa ini (agar tidak dobel)
$bukuAktif = array_column(db_all("SELECT id_buku FROM peminjaman WHERE id_anggota = ? AND status IN ('menunggu','dipinjam','diajukan_kembali')", [$id]), 'id_buku');

// ---------- notifikasi ----------
$notifs = [];
foreach (db_all("SELECT b.judul, p.tanggal_jatuh_tempo FROM peminjaman p JOIN buku b USING(id_buku)
                 WHERE p.id_anggota = ? AND p.status = 'dipinjam' AND p.tanggal_jatuh_tempo <= CURDATE() + INTERVAL 2 DAY
                 ORDER BY p.tanggal_jatuh_tempo", [$id]) as $n) {
    $sh = sisa_hari($n['tanggal_jatuh_tempo']);
    $notifs[] = ['icon' => $sh < 0 ? 'fa-triangle-exclamation' : 'fa-clock', 'url' => '?page=pinjaman',
        'text' => '"' . $n['judul'] . '" ' . ($sh < 0 ? 'terlambat ' . abs($sh) . ' hari!' : ($sh === 0 ? 'jatuh tempo hari ini' : "jatuh tempo $sh hari lagi"))];
}
if ($dendaBelum) $notifs[] = ['icon' => 'fa-money-bill-wave', 'url' => '?page=denda', 'text' => 'Denda belum dibayar: ' . rupiah($dendaBelum)];
foreach (db_all("SELECT b.judul FROM peminjaman p JOIN buku b USING(id_buku) WHERE p.id_anggota = ? AND p.status = 'ditolak' AND p.tanggal_pengajuan >= NOW() - INTERVAL 3 DAY", [$id]) as $n) {
    $notifs[] = ['icon' => 'fa-circle-xmark', 'url' => '?page=riwayat', 'text' => 'Pengajuan "' . $n['judul'] . '" ditolak'];
}

// ---------- menu ----------
$menu = [
    'beranda'  => ['Beranda', 'fa-house'],
    'katalog'  => ['Katalog Buku', 'fa-book-open'],
    'pinjaman' => ['Buku Dipinjam', 'fa-bookmark', $hitung['dipinjam'] + $hitung['diajukan_kembali']],
    'pengajuan' => ['Daftar Tunggu', 'fa-hourglass-half', $hitung['menunggu']],
    'riwayat'  => ['Riwayat', 'fa-clock-rotate-left'],
    'denda'    => ['Denda', 'fa-money-bill-wave', $dendaBelum ? 1 : 0],
    'profil'   => ['Profil', 'fa-user'],
];
$page = $_GET['page'] ?? 'beranda';
if (!isset($menu[$page])) $page = 'beranda';

$base   = 'dashboard_siswa.php';
$title  = $menu[$page][0];
$user   = ['nama' => $anggota['nama'], 'sub' => $anggota['kelas'] . ' · ' . $anggota['nomor_anggota'], 'foto' => null];
$search = ['placeholder' => 'Cari judul, penulis, atau kode buku...', 'page' => 'katalog'];
$menuUser = [['Profil Saya', '?page=profil', 'fa-user'], ['Ganti Password', '?page=profil#password', 'fa-key']];

/** Kartu buku untuk katalog/beranda. */
function kartu_buku(array $b, ?string $blokir, array $bukuAktif): void
{
    $cover = cover_url($b['cover']);
    $sudah = in_array($b['id_buku'], $bukuAktif);
    $data = [
        'id_buku' => $b['id_buku'], 'judul' => $b['judul'], 'penulis' => $b['penulis'] ?: '-', 'penerbit' => $b['penerbit'] ?: '-',
        'tahun_terbit' => $b['tahun_terbit'] ?: '-', 'kode_buku' => $b['kode_buku'], 'kategori' => $b['kategori'], 'rak' => $b['rak'] ?: '-',
        'deskripsi' => $b['deskripsi'] ?: 'Belum ada deskripsi.', 'stok' => (int)$b['stok'], 'stok_label' => (int)$b['stok'] > 0 ? $b['stok'] . ' tersedia' : 'Habis',
        'cover' => $cover, 'blok' => $sudah ? 'Sudah diajukan/dipinjam' : $blokir,
    ];
    ?>
    <div class="book-card card card-hover reveal flex flex-col p-3" data-search="<?= e($b['judul'] . ' ' . $b['penulis'] . ' ' . $b['kode_buku'] . ' ' . $b['kategori']) ?>">
      <button type="button" class="book-cover mb-3 w-full text-left" data-modal-open="modal-buku" data-book='<?= e(json_encode($data)) ?>'>
        <?php if ($cover): ?><img src="<?= e($cover) ?>" alt="<?= e($b['judul']) ?>" loading="lazy">
        <?php else: ?><div class="book-ph"><i class="fa-solid fa-book text-4xl"></i><p class="mt-2 line-clamp-3 text-xs font-semibold"><?= e($b['judul']) ?></p></div><?php endif; ?>
        <span class="absolute right-2 top-2 rounded-md px-2 py-0.5 text-[11px] font-semibold <?= $b['stok'] > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600' ?>"><?= $b['stok'] > 0 ? (int)$b['stok'] . ' Stok' : 'Habis' ?></span>
        <span class="absolute left-2 top-2 rounded-md bg-white/90 px-2 py-0.5 text-[10px] font-medium text-gold-deep"><?= e($b['kategori']) ?></span>
      </button>
      <h3 class="truncate text-sm font-semibold text-ink" title="<?= e($b['judul']) ?>"><?= e($b['judul']) ?></h3>
      <p class="truncate text-xs text-soft"><?= e($b['penulis'] ?: '-') ?> · <?= e($b['tahun_terbit'] ?: '-') ?></p>
      <div class="mt-auto pt-3">
        <?php if ($sudah): ?>
          <button class="btn btn-sm w-full bg-gold/20 text-gold-deep" disabled><i class="fa-solid fa-check"></i> Diajukan</button>
        <?php elseif ($b['stok'] <= 0): ?>
          <button class="btn btn-sm w-full bg-red-100 text-red-500" disabled>Stok habis</button>
        <?php else: ?>
          <button type="button" class="btn btn-gold btn-sm w-full" data-modal-open="modal-buku" data-book='<?= e(json_encode($data)) ?>'><i class="fa-solid fa-hand-holding-heart"></i> Pinjam</button>
        <?php endif; ?>
      </div>
    </div>
    <?php
}

include 'includes/layout_start.php';
include "pages/siswa/$page.php";
?>

<!-- ===== Modal detail buku ===== -->
<div id="modal-buku" class="modal">
  <div class="modal-box max-w-2xl">
    <div class="grid gap-6 p-6 sm:grid-cols-[180px_1fr]">
      <div class="book-cover w-full shadow-md-soft">
        <img data-book-img class="hidden" alt="">
        <div data-book-ph class="book-ph"><i class="fa-solid fa-book text-5xl"></i></div>
      </div>
      <div>
        <div class="mb-1 flex items-start justify-between gap-3">
          <span class="rounded-md bg-gold/20 px-2 py-0.5 text-xs font-semibold text-gold-deep" data-text="kategori"></span>
          <button class="text-2xl leading-none text-soft hover:text-ink" data-modal-close>&times;</button>
        </div>
        <h3 class="text-xl font-bold text-ink" data-text="judul"></h3>
        <p class="mb-3 text-sm text-soft">oleh <span data-text="penulis"></span></p>
        <p class="mb-4 text-sm leading-relaxed text-ink/80" data-text="deskripsi"></p>
        <dl class="mb-5 grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
          <div><dt class="text-soft">Kode</dt><dd class="font-semibold" data-text="kode_buku"></dd></div>
          <div><dt class="text-soft">Penerbit</dt><dd class="font-semibold" data-text="penerbit"></dd></div>
          <div><dt class="text-soft">Tahun</dt><dd class="font-semibold" data-text="tahun_terbit"></dd></div>
          <div><dt class="text-soft">Rak</dt><dd class="font-semibold" data-text="rak"></dd></div>
          <div><dt class="text-soft">Ketersediaan</dt><dd class="font-semibold text-hijau" data-text="stok_label"></dd></div>
          <div><dt class="text-soft">Lama pinjam</dt><dd class="font-semibold"><?= (int)setting('lama_pinjam', 7) ?> hari</dd></div>
        </dl>
        <form method="post" action="aksi_siswa.php">
          <?= csrf_field() ?>
          <input type="hidden" name="aksi" value="pinjam">
          <input type="hidden" name="id_buku">
          <button class="btn btn-gold w-full" data-book-submit></button>
        </form>
        <p class="mt-2 text-center text-[11px] text-soft">Pengajuan akan diproses pustakawan. Ambil buku di perpustakaan setelah disetujui.</p>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/layout_end.php'; ?>
