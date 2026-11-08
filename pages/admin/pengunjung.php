<?php
$tgl = $_GET['tgl'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) $tgl = date('Y-m-d');
$list = db_all("SELECT k.*, a.nama, a.kelas FROM kunjungan k LEFT JOIN anggota a USING(id_anggota) WHERE DATE(k.waktu) = ? ORDER BY k.waktu DESC", [$tgl]);
$minggu = (int)db_value("SELECT COUNT(*) FROM kunjungan WHERE waktu >= CURDATE() - INTERVAL 6 DAY");
$bulan = (int)db_value("SELECT COUNT(*) FROM kunjungan WHERE YEAR(waktu) = YEAR(CURDATE()) AND MONTH(waktu) = MONTH(CURDATE())");
$anggotaList = db_all("SELECT id_anggota, nama, kelas FROM anggota WHERE status = 'aktif' ORDER BY nama");
page_head('Buku Tamu / Pengunjung', 'Siswa tercatat otomatis saat login; tamu lain dicatat manual.',
    '<button class="btn btn-gold" data-modal-open="modal-tamu"><i class="fa-solid fa-user-plus"></i> Catat Pengunjung</button>');
?>
<div class="mb-6 grid gap-4 sm:grid-cols-3">
  <div class="card card-hover p-5"><p class="text-sm text-soft">Tanggal dipilih</p><p class="mt-1 text-3xl font-bold" data-count="<?= count($list) ?>">0</p></div>
  <div class="card card-hover p-5"><p class="text-sm text-soft">7 hari terakhir</p><p class="mt-1 text-3xl font-bold" data-count="<?= $minggu ?>">0</p></div>
  <div class="card card-hover p-5"><p class="text-sm text-soft">Bulan ini</p><p class="mt-1 text-3xl font-bold" data-count="<?= $bulan ?>">0</p></div>
</div>
<form method="get" class="card mb-4 flex flex-wrap items-center gap-3 p-4">
  <input type="hidden" name="page" value="pengunjung">
  <label class="text-sm font-medium">Tanggal</label>
  <input type="date" name="tgl" value="<?= e($tgl) ?>" max="<?= date('Y-m-d') ?>" class="input w-auto" onchange="this.form.submit()">
  <a href="?page=pengunjung" class="btn btn-ghost btn-sm">Hari ini</a>
  <div class="ml-auto"><?= filter_box('#tbl-tamu tbody tr', 'Cari pengunjung...') ?></div>
</form>
<div class="card overflow-x-auto p-2">
  <table class="tbl" id="tbl-tamu">
    <thead><tr><th>Jam</th><th>Nama</th><th>Jenis</th><th>Keperluan</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($list as $k): ?>
      <tr>
        <td class="font-mono text-xs"><?= date('H:i', strtotime($k['waktu'])) ?></td>
        <td><p class="font-semibold"><?= e($k['nama'] ?? $k['nama_tamu']) ?></p><p class="text-xs text-soft"><?= e($k['kelas'] ?? '') ?></p></td>
        <td><?= $k['id_anggota'] ? '<span class="rounded-full bg-gold/20 px-2 py-0.5 text-xs font-semibold text-gold-deep">Anggota</span>' : '<span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600">Tamu</span>' ?></td>
        <td class="text-sm"><?= e($k['keperluan']) ?></td>
        <td class="text-right">
          <form method="post" action="aksi_admin.php" data-confirm="Hapus catatan kunjungan ini?" data-danger data-ok="Hapus">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="kunjungan_hapus"><input type="hidden" name="id_kunjungan" value="<?= $k['id_kunjungan'] ?>">
            <button class="btn btn-ghost btn-sm text-red-500"><i class="fa-solid fa-trash"></i></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$list): ?><tr><td colspan="5" class="p-10 text-center text-soft">Belum ada pengunjung pada tanggal ini.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<div id="modal-tamu" class="modal">
  <form method="post" action="aksi_admin.php" class="modal-box max-w-md p-6">
    <?= csrf_field() ?><input type="hidden" name="aksi" value="kunjungan_tambah">
    <h3 class="mb-4 text-lg font-semibold">Catat Pengunjung</h3>
    <div class="space-y-4">
      <div><label class="label">Anggota (opsional)</label>
        <select name="id_anggota" class="input"><option value="">— bukan anggota / tamu —</option>
          <?php foreach ($anggotaList as $a): ?><option value="<?= $a['id_anggota'] ?>"><?= e($a['nama'] . ' · ' . $a['kelas']) ?></option><?php endforeach; ?>
        </select></div>
      <div><label class="label">Nama Tamu (jika bukan anggota)</label><input name="nama_tamu" maxlength="100" class="input" placeholder="Nama guru / tamu"></div>
      <div><label class="label">Keperluan</label>
        <select name="keperluan" class="input"><?php foreach (['Membaca', 'Meminjam buku', 'Mengembalikan buku', 'Mengerjakan tugas', 'Mencari referensi', 'Lainnya'] as $o): ?><option><?= $o ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="mt-6 flex justify-end gap-2"><button type="button" class="btn btn-ghost" data-modal-close>Batal</button><button class="btn btn-gold">Simpan</button></div>
  </form>
</div>
