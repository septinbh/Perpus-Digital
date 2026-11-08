<?php
/** cetak_struk.php — bukti pembayaran denda (siap cetak). */
require 'config.php';
require_staf();
$d = db_one("SELECT d.*, a.nama, a.kelas, a.nis, a.nomor_anggota, b.judul, b.kode_buku, p.tanggal_pinjam, p.tanggal_jatuh_tempo, k.tanggal_kembali
             FROM denda d JOIN anggota a USING(id_anggota) JOIN peminjaman p USING(id_peminjaman) JOIN buku b ON b.id_buku = p.id_buku
             LEFT JOIN pengembalian k ON k.id_peminjaman = p.id_peminjaman WHERE d.id_denda = ?", [(int)($_GET['id'] ?? 0)]);
if (!$d) { http_response_code(404); exit('Data denda tidak ditemukan.'); }
$title = 'Bukti Pembayaran Denda';
include 'includes/head.php';
?>
<body class="bg-krem p-6">
<div class="mx-auto max-w-sm rounded-2xl border border-gold-dark/40 bg-white p-6 font-mono text-sm shadow-md-soft">
  <div class="mb-4 border-b border-dashed border-gray-300 pb-4 text-center">
    <img src="assets/img/logosmk.png" class="mx-auto mb-2 h-12" alt="">
    <p class="font-sans font-bold">PERPUSTAKAAN SMKN 1 KAMAL</p>
    <p class="text-xs text-soft">Bukti Pembayaran Denda</p>
  </div>
  <table class="w-full text-xs">
    <?php foreach ([
        'No. Bukti' => 'DND-' . str_pad((string)$d['id_denda'], 5, '0', STR_PAD_LEFT),
        'Tanggal Bayar' => tgl($d['tanggal_bayar'], true),
        'Nama' => $d['nama'], 'Kelas' => $d['kelas'], 'NIS' => $d['nis'],
        'Buku' => $d['judul'] . ' (' . $d['kode_buku'] . ')',
        'Jatuh Tempo' => tgl($d['tanggal_jatuh_tempo']), 'Dikembalikan' => tgl($d['tanggal_kembali']),
        'Keterangan' => $d['keterangan'], 'Status' => strtoupper(str_replace('_', ' ', $d['status'])),
    ] as $k => $v): ?>
    <tr><td class="py-0.5 pr-2 align-top text-soft"><?= $k ?></td><td class="py-0.5 text-right"><?= e($v) ?></td></tr>
    <?php endforeach; ?>
  </table>
  <div class="mt-4 flex justify-between border-t border-dashed border-gray-300 pt-4 text-base font-bold"><span>TOTAL</span><span><?= rupiah($d['jumlah']) ?></span></div>
  <p class="mt-6 text-center text-[11px] text-soft">Terima kasih. Simpan bukti ini sebagai tanda pelunasan.</p>
</div>
<div class="no-print mt-4 text-center"><button onclick="print()" class="btn btn-gold"><i class="fa-solid fa-print"></i> Cetak</button></div>
<?php include 'includes/foot.php'; ?>
