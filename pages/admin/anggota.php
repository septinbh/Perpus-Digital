<?php
$fStatus = $_GET['status'] ?? '';
$where = $fStatus === 'aktif' || $fStatus === 'nonaktif' ? "WHERE a.status = '$fStatus'" : '';
$list = db_all("SELECT a.*,
   (SELECT COUNT(*) FROM peminjaman p WHERE p.id_anggota = a.id_anggota AND p.status IN ('dipinjam','diajukan_kembali')) dipinjam,
   (SELECT COUNT(*) FROM peminjaman p WHERE p.id_anggota = a.id_anggota) total_pinjam,
   (SELECT COALESCE(SUM(jumlah),0) FROM denda d WHERE d.id_anggota = a.id_anggota AND d.status = 'belum_dibayar') denda
   FROM anggota a $where ORDER BY a.nama");
$lastNo = db_value("SELECT nomor_anggota FROM anggota WHERE nomor_anggota REGEXP '^A[0-9]+$' ORDER BY CAST(SUBSTRING(nomor_anggota,2) AS UNSIGNED) DESC LIMIT 1");
$kosong = ['id_anggota' => '', 'nomor_anggota' => 'A' . str_pad((string)((int)substr($lastNo ?? 'A0', 1) + 1), 3, '0', STR_PAD_LEFT),
    'nis' => '', 'nama' => '', 'kelas' => '', 'jurusan' => '', 'no_hp' => '', 'judul_modal' => 'Tambah Anggota', 'info_pw' => 'Password awal otomatis = NIS. Siswa wajib menggantinya saat login pertama.'];
$jurusan = array_column(db_all("SELECT DISTINCT jurusan FROM anggota ORDER BY jurusan"), 'jurusan');

page_head('Data Anggota', count($list) . ' siswa terdaftar',
    '<button class="btn btn-gold" data-modal-open="modal-anggota" data-fill=\'' . e(json_encode($kosong)) . '\'><i class="fa-solid fa-user-plus"></i> Tambah Anggota</button>');
?>
<div class="card mb-4 flex flex-wrap items-center gap-3 p-4">
  <?= filter_box('#tbl-anggota tbody tr', 'Cari nama, NIS, kelas...') ?>
  <div class="flex gap-1 rounded-xl bg-krem p-1 text-sm">
    <?php foreach (['' => 'Semua', 'aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'] as $k => $v): ?>
      <a href="?page=anggota&status=<?= $k ?>" class="rounded-lg px-3 py-1.5 font-medium <?= $fStatus === $k ? 'bg-gold text-black shadow-gold' : 'text-soft hover:text-ink' ?>"><?= $v ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="card overflow-x-auto p-2">
  <table class="tbl" id="tbl-anggota">
    <thead><tr><th>Anggota</th><th>NIS / No. Anggota</th><th>Kelas</th><th class="text-center">Dipinjam</th><th>Denda</th><th>Login Terakhir</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($list as $a): $fill = $a + ['judul_modal' => 'Edit Anggota', 'info_pw' => 'Password tidak berubah. Gunakan tombol Reset Password bila siswa lupa.']; unset($fill['password']); ?>
      <tr data-search="<?= e($a['nama'] . ' ' . $a['nis'] . ' ' . $a['nomor_anggota'] . ' ' . $a['kelas'] . ' ' . $a['jurusan']) ?>">
        <td>
          <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gold/30 font-bold"><?= e(mb_strtoupper(mb_substr($a['nama'], 0, 1))) ?></span>
            <div><p class="font-semibold"><?= e($a['nama']) ?></p><p class="text-xs text-soft"><?= e($a['no_hp'] ?: '-') ?></p></div>
          </div>
        </td>
        <td class="font-mono text-xs"><?= e($a['nis']) ?><br><span class="text-soft"><?= e($a['nomor_anggota']) ?></span></td>
        <td class="text-xs"><?= e($a['kelas']) ?><br><span class="text-soft"><?= e($a['jurusan']) ?></span></td>
        <td class="text-center"><?= (int)$a['dipinjam'] ?> <span class="text-xs text-soft">/ <?= (int)$a['total_pinjam'] ?></span></td>
        <td class="<?= $a['denda'] ? 'font-semibold text-merah' : 'text-soft' ?>"><?= $a['denda'] ? rupiah($a['denda']) : '-' ?></td>
        <td class="text-xs"><?= tgl($a['last_login'], true) ?><?= $a['harus_ganti_password'] ? '<br><span class="text-[11px] text-amber-600"><i class="fa-solid fa-key"></i> pakai password awal</span>' : '' ?></td>
        <td><?= status_badge($a['status']) ?></td>
        <td class="whitespace-nowrap text-right">
          <div class="inline-flex gap-1">
            <button class="btn btn-sm bg-blue-500 text-white hover:bg-blue-600" title="Edit" data-modal-open="modal-anggota" data-fill='<?= e(json_encode($fill)) ?>'><i class="fa-solid fa-pen"></i></button>
            <form method="post" action="aksi_admin.php" data-confirm="Reset password <?= e($a['nama']) ?> menjadi NIS (<?= e($a['nis']) ?>)? Siswa wajib mengganti saat login.">
              <?= csrf_field() ?><input type="hidden" name="aksi" value="anggota_reset"><input type="hidden" name="id_anggota" value="<?= $a['id_anggota'] ?>">
              <button class="btn btn-sm btn-outline" title="Reset password ke NIS"><i class="fa-solid fa-key"></i></button>
            </form>
            <form method="post" action="aksi_admin.php">
              <?= csrf_field() ?><input type="hidden" name="aksi" value="anggota_status"><input type="hidden" name="id_anggota" value="<?= $a['id_anggota'] ?>">
              <button class="btn btn-sm border border-gray-200 <?= $a['status'] === 'aktif' ? 'text-soft' : 'text-hijau' ?> hover:bg-krem" title="<?= $a['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>"><i class="fa-solid <?= $a['status'] === 'aktif' ? 'fa-user-slash' : 'fa-user-check' ?>"></i></button>
            </form>
            <form method="post" action="aksi_admin.php" data-confirm="Hapus anggota <?= e($a['nama']) ?>? Anggota yang punya riwayat peminjaman tidak bisa dihapus (nonaktifkan saja)." data-danger data-ok="Hapus">
              <?= csrf_field() ?><input type="hidden" name="aksi" value="anggota_hapus"><input type="hidden" name="id_anggota" value="<?= $a['id_anggota'] ?>">
              <button class="btn btn-red btn-sm" title="Hapus"><i class="fa-solid fa-trash"></i></button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$list): ?><tr><td colspan="8" class="p-10 text-center text-soft">Belum ada anggota.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<!-- MODAL ANGGOTA -->
<div id="modal-anggota" class="modal">
  <form method="post" action="aksi_admin.php" class="modal-box">
    <?= csrf_field() ?><input type="hidden" name="aksi" value="anggota_simpan"><input type="hidden" name="id_anggota">
    <div class="flex items-center justify-between border-b border-gold/20 px-6 py-4">
      <h3 class="text-lg font-semibold" data-text="judul_modal">Tambah Anggota</h3>
      <button type="button" class="text-2xl text-soft hover:text-ink" data-modal-close>&times;</button>
    </div>
    <div class="grid gap-4 p-6 sm:grid-cols-2">
      <div class="sm:col-span-2"><label class="label">Nama Lengkap *</label><input name="nama" required maxlength="100" class="input"></div>
      <div><label class="label">NIS *</label><input name="nis" required maxlength="20" pattern="[0-9A-Za-z.\-]+" class="input font-mono"></div>
      <div><label class="label">Nomor Anggota *</label><input name="nomor_anggota" required maxlength="30" class="input font-mono"></div>
      <div><label class="label">Kelas *</label><input name="kelas" required maxlength="20" placeholder="XI RPL 1" class="input"></div>
      <div><label class="label">Jurusan *</label><input name="jurusan" required maxlength="50" list="list-jurusan" class="input">
        <datalist id="list-jurusan"><?php foreach (array_unique(array_merge($jurusan, ['RPL', 'TKJ', 'DKV', 'AKL', 'OTKP', 'BDP'])) as $j): ?><option value="<?= e($j) ?>"><?php endforeach; ?></datalist></div>
      <div class="sm:col-span-2"><label class="label">No. HP</label><input name="no_hp" maxlength="20" class="input" placeholder="08xxxxxxxxxx"></div>
      <p class="rounded-xl bg-gold/10 p-3 text-xs text-ink sm:col-span-2"><i class="fa-solid fa-key mr-1 text-gold-dark"></i><span data-text="info_pw"></span></p>
    </div>
    <div class="flex justify-end gap-2 border-t border-gold/20 px-6 py-4">
      <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
      <button class="btn btn-gold"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
    </div>
  </form>
</div>
