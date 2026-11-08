<?php
/**
 * aksi_admin.php — semua aksi POST dari dashboard pustakawan.
 */
require 'config.php';
$staf = require_staf();
$uid  = $staf['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('dashboard_admin.php');
verify_csrf();

$aksi = $_POST['aksi'] ?? '';
$back = back_url('dashboard_admin.php');
$str  = fn($k, $max = 255) => mb_substr(trim((string)($_POST[$k] ?? '')), 0, $max);
$int  = fn($k) => (int)($_POST[$k] ?? 0);

try {
    switch ($aksi) {

    // =============================== BUKU ===============================
    case 'buku_simpan':
        $idb = $int('id_buku');
        $d = [
            'kode_buku' => $str('kode_buku', 50), 'judul' => $str('judul', 200), 'penulis' => $str('penulis', 150) ?: null,
            'penerbit' => $str('penerbit', 150) ?: null, 'tahun_terbit' => $int('tahun_terbit') ?: null,
            'kategori' => $str('kategori', 50) ?: 'Umum', 'rak' => $str('rak', 20) ?: null,
            'deskripsi' => trim((string)($_POST['deskripsi'] ?? '')) ?: null, 'stok' => max(0, $int('stok')),
        ];
        if ($d['judul'] === '' || $d['kode_buku'] === '') { flash('error', 'Judul dan kode buku wajib diisi.'); break; }
        if ($d['tahun_terbit'] && ($d['tahun_terbit'] < 1901 || $d['tahun_terbit'] > (int)date('Y'))) { flash('error', 'Tahun terbit tidak valid.'); break; }
        if (db_value("SELECT COUNT(*) FROM buku WHERE kode_buku = ? AND id_buku <> ?", [$d['kode_buku'], $idb])) { flash('error', 'Kode buku "' . $d['kode_buku'] . '" sudah dipakai.'); break; }

        $lama = $idb ? db_one("SELECT cover FROM buku WHERE id_buku = ?", [$idb]) : null;
        $cover = $lama['cover'] ?? null;
        $baru = upload_cover($_FILES['cover_file'] ?? []);
        if ($baru || !empty($_POST['hapus_cover'])) {
            if ($cover && str_starts_with($cover, 'uploads/') && is_file(BASE_DIR . '/' . $cover)) @unlink(BASE_DIR . '/' . $cover);
            $cover = $baru;
        }
        $d['cover'] = $cover;

        if ($idb) {
            db_exec("UPDATE buku SET kode_buku=?, judul=?, penulis=?, penerbit=?, tahun_terbit=?, kategori=?, rak=?, deskripsi=?, stok=?, cover=? WHERE id_buku=?", [...array_values($d), $idb]);
            flash('success', 'Buku "' . $d['judul'] . '" berhasil diperbarui.');
        } else {
            db_exec("INSERT INTO buku (kode_buku, judul, penulis, penerbit, tahun_terbit, kategori, rak, deskripsi, stok, cover) VALUES (?,?,?,?,?,?,?,?,?,?)", array_values($d));
            flash('success', 'Buku "' . $d['judul'] . '" berhasil ditambahkan.');
        }
        break;

    case 'buku_hapus':
        $idb = $int('id_buku');
        $b = db_one("SELECT judul, cover FROM buku WHERE id_buku = ?", [$idb]);
        if (!$b) { flash('error', 'Buku tidak ditemukan.'); break; }
        if (db_value("SELECT COUNT(*) FROM peminjaman WHERE id_buku = ?", [$idb])) {
            flash('error', 'Buku "' . $b['judul'] . '" punya riwayat peminjaman sehingga tidak bisa dihapus. Ubah stoknya menjadi 0 bila sudah tidak tersedia.');
            break;
        }
        db_exec("DELETE FROM buku WHERE id_buku = ?", [$idb]);
        if ($b['cover'] && str_starts_with($b['cover'], 'uploads/')) @unlink(BASE_DIR . '/' . $b['cover']);
        flash('success', 'Buku "' . $b['judul'] . '" dihapus.');
        break;

    // =============================== ANGGOTA ===============================
    case 'anggota_simpan':
        $ida = $int('id_anggota');
        $d = ['nomor_anggota' => $str('nomor_anggota', 30), 'nis' => $str('nis', 20), 'nama' => $str('nama', 100),
              'kelas' => $str('kelas', 20), 'jurusan' => $str('jurusan', 50), 'no_hp' => preg_replace('/[^0-9+]/', '', $str('no_hp', 20)) ?: null];
        if (in_array('', [$d['nomor_anggota'], $d['nis'], $d['nama'], $d['kelas'], $d['jurusan']], true)) { flash('error', 'Lengkapi semua data wajib.'); break; }
        if (db_value("SELECT COUNT(*) FROM anggota WHERE nis = ? AND id_anggota <> ?", [$d['nis'], $ida])) { flash('error', 'NIS ' . $d['nis'] . ' sudah terdaftar.'); break; }
        if (db_value("SELECT COUNT(*) FROM anggota WHERE nomor_anggota = ? AND id_anggota <> ?", [$d['nomor_anggota'], $ida])) { flash('error', 'Nomor anggota ' . $d['nomor_anggota'] . ' sudah dipakai.'); break; }
        if ($ida) {
            db_exec("UPDATE anggota SET nomor_anggota=?, nis=?, nama=?, kelas=?, jurusan=?, no_hp=? WHERE id_anggota=?", [...array_values($d), $ida]);
            flash('success', 'Data ' . $d['nama'] . ' diperbarui.');
        } else {
            // password awal = NIS, wajib diganti saat login pertama
            db_exec("INSERT INTO anggota (nomor_anggota, nis, nama, kelas, jurusan, no_hp, password, harus_ganti_password) VALUES (?,?,?,?,?,?,?,1)",
                [...array_values($d), hash_password($d['nis'])]);
            flash('success', 'Anggota ' . $d['nama'] . ' ditambahkan. Password awal: NIS (' . $d['nis'] . ').');
        }
        break;

    case 'anggota_reset':
        $a = db_one("SELECT nama, nis FROM anggota WHERE id_anggota = ?", [$int('id_anggota')]);
        if ($a) {
            db_exec("UPDATE anggota SET password = ?, harus_ganti_password = 1 WHERE id_anggota = ?", [hash_password($a['nis']), $int('id_anggota')]);
            flash('success', 'Password ' . $a['nama'] . ' direset ke NIS (' . $a['nis'] . '). Siswa wajib menggantinya saat login.');
        }
        break;

    case 'anggota_status':
        $a = db_one("SELECT nama, status FROM anggota WHERE id_anggota = ?", [$int('id_anggota')]);
        if ($a) {
            $baru = $a['status'] === 'aktif' ? 'nonaktif' : 'aktif';
            db_exec("UPDATE anggota SET status = ? WHERE id_anggota = ?", [$baru, $int('id_anggota')]);
            flash('success', $a['nama'] . ' sekarang ' . $baru . '.');
        }
        break;

    case 'anggota_hapus':
        $ida = $int('id_anggota');
        $a = db_one("SELECT nama FROM anggota WHERE id_anggota = ?", [$ida]);
        if (!$a) break;
        if (db_value("SELECT COUNT(*) FROM peminjaman WHERE id_anggota = ?", [$ida])) {
            flash('error', $a['nama'] . ' punya riwayat peminjaman. Nonaktifkan saja akunnya.');
            break;
        }
        db_exec("DELETE FROM anggota WHERE id_anggota = ?", [$ida]);
        flash('success', 'Anggota ' . $a['nama'] . ' dihapus.');
        break;

    // =============================== PEMINJAMAN ===============================
    case 'pinjam_setujui':
        $idp = $int('id_peminjaman');
        $conn->begin_transaction();
        $p = db_one("SELECT p.*, b.judul, b.stok, a.nama FROM peminjaman p JOIN buku b USING(id_buku) JOIN anggota a USING(id_anggota)
                     WHERE p.id_peminjaman = ? AND p.status = 'menunggu' FOR UPDATE", [$idp]);
        if (!$p) { $conn->rollback(); flash('error', 'Pengajuan tidak ditemukan / sudah diproses.'); break; }
        if ($p['stok'] < 1) { $conn->rollback(); flash('error', 'Stok "' . $p['judul'] . '" habis. Tolak pengajuan atau tunggu buku kembali.'); break; }
        $lama = (int)setting('lama_pinjam', 7);
        db_exec("UPDATE peminjaman SET status='dipinjam', tanggal_pinjam=CURDATE(), tanggal_jatuh_tempo=CURDATE() + INTERVAL ? DAY, diproses_oleh=? WHERE id_peminjaman=?", [$lama, $uid, $idp]);
        db_exec("UPDATE buku SET stok = stok - 1 WHERE id_buku = ?", [(int)$p['id_buku']]);
        $conn->commit();
        flash('success', 'Peminjaman "' . $p['judul'] . '" oleh ' . $p['nama'] . ' disetujui. Jatuh tempo ' . tgl(date('Y-m-d', strtotime("+$lama day"))) . '.');
        break;

    case 'pinjam_tolak':
        $idp = $int('id_peminjaman');
        $catatan = $str('catatan') ?: 'Ditolak oleh pustakawan';
        if (db_exec("UPDATE peminjaman SET status='ditolak', catatan=?, diproses_oleh=? WHERE id_peminjaman=? AND status='menunggu'", [$catatan, $uid, $idp])) {
            flash('success', 'Pengajuan ditolak.');
        } else flash('error', 'Pengajuan tidak ditemukan / sudah diproses.');
        break;

    case 'pinjam_langsung':
        $ida = $int('id_anggota'); $idb = $int('id_buku');
        $lama = max(1, min(60, $int('lama') ?: (int)setting('lama_pinjam', 7)));
        $tglPinjam = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['tanggal_pinjam'] ?? '') ? $_POST['tanggal_pinjam'] : date('Y-m-d');
        if ($tglPinjam > date('Y-m-d')) $tglPinjam = date('Y-m-d');
        $a = db_one("SELECT nama, status FROM anggota WHERE id_anggota = ?", [$ida]);
        $conn->begin_transaction();
        $b = db_one("SELECT judul, stok FROM buku WHERE id_buku = ? FOR UPDATE", [$idb]);
        $aktif = (int)db_value("SELECT COUNT(*) FROM peminjaman WHERE id_anggota = ? AND status IN ('menunggu','dipinjam','diajukan_kembali')", [$ida]);
        $dobel = (int)db_value("SELECT COUNT(*) FROM peminjaman WHERE id_anggota = ? AND id_buku = ? AND status IN ('menunggu','dipinjam','diajukan_kembali')", [$ida, $idb]);
        $err = null;
        if (!$a || $a['status'] !== 'aktif') $err = 'Anggota tidak ditemukan / tidak aktif.';
        elseif (!$b) $err = 'Buku tidak ditemukan.';
        elseif ($b['stok'] < 1) $err = 'Stok buku habis.';
        elseif ($dobel) $err = $a['nama'] . ' sudah meminjam / mengajukan buku ini.';
        elseif ($aktif >= (int)setting('maks_pinjam', 3)) $err = $a['nama'] . ' sudah mencapai batas maksimal peminjaman.';
        if ($err) { $conn->rollback(); flash('error', $err); redirect('dashboard_admin.php?page=peminjaman#langsung'); }
        db_exec("INSERT INTO peminjaman (id_anggota, id_buku, tanggal_pinjam, tanggal_jatuh_tempo, status, diproses_oleh, catatan)
                 VALUES (?, ?, ?, DATE_ADD(?, INTERVAL ? DAY), 'dipinjam', ?, 'Dicatat langsung di meja layanan')", [$ida, $idb, $tglPinjam, $tglPinjam, $lama, $uid]);
        db_exec("UPDATE buku SET stok = stok - 1 WHERE id_buku = ?", [$idb]);
        $conn->commit();
        flash('success', 'Peminjaman "' . $b['judul'] . '" untuk ' . $a['nama'] . ' tersimpan.');
        redirect('dashboard_admin.php?page=peminjaman#aktif');

    // =============================== PENGEMBALIAN ===============================
    case 'kembali_proses':
        $idp = $int('id_peminjaman');
        $kondisi = in_array($_POST['kondisi'] ?? '', ['baik', 'rusak', 'hilang'], true) ? $_POST['kondisi'] : 'baik';
        $tglK = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['tanggal_kembali'] ?? '') ? $_POST['tanggal_kembali'] : date('Y-m-d');
        if ($tglK > date('Y-m-d')) $tglK = date('Y-m-d');

        $conn->begin_transaction();
        $p = db_one("SELECT p.*, b.judul, a.nama FROM peminjaman p JOIN buku b USING(id_buku) JOIN anggota a USING(id_anggota)
                     WHERE p.id_peminjaman = ? AND p.status IN ('dipinjam','diajukan_kembali') FOR UPDATE", [$idp]);
        if (!$p) { $conn->rollback(); flash('error', 'Data peminjaman tidak ditemukan / sudah dikembalikan.'); break; }
        if ($tglK < $p['tanggal_pinjam']) $tglK = $p['tanggal_pinjam'];

        $telat = hari_terlambat($p['tanggal_jatuh_tempo'], $tglK);
        $dendaTelat = $telat * (int)setting('denda_per_hari', 1000);
        $dendaKondisi = $kondisi === 'rusak' ? (int)setting('denda_rusak', 0) : ($kondisi === 'hilang' ? (int)setting('denda_hilang', 0) : 0);
        $total = $dendaTelat + $dendaKondisi;

        db_exec("INSERT INTO pengembalian (id_peminjaman, tanggal_kembali, terlambat_hari, kondisi, denda, diproses_oleh) VALUES (?,?,?,?,?,?)",
            [$idp, $tglK, $telat, $kondisi, $total, $uid]);
        db_exec("UPDATE peminjaman SET status = 'dikembalikan' WHERE id_peminjaman = ?", [$idp]);
        if ($kondisi !== 'hilang') db_exec("UPDATE buku SET stok = stok + 1 WHERE id_buku = ?", [(int)$p['id_buku']]);
        if ($total > 0) {
            $ket = [];
            if ($telat) $ket[] = "Terlambat $telat hari";
            if ($dendaKondisi) $ket[] = 'Buku ' . $kondisi;
            db_exec("INSERT INTO denda (id_peminjaman, id_anggota, jumlah, keterangan) VALUES (?,?,?,?)", [$idp, (int)$p['id_anggota'], $total, implode(' + ', $ket)]);
        }
        $conn->commit();
        flash($total ? 'warning' : 'success', 'Pengembalian "' . $p['judul'] . '" oleh ' . $p['nama'] . ' dicatat.' . ($total ? ' Denda ' . rupiah($total) . ' ditagihkan.' : ' Tanpa denda.'));
        break;

    // =============================== DENDA ===============================
    case 'denda_lunas':
        if (db_exec("UPDATE denda SET status='lunas', tanggal_bayar=NOW() WHERE id_denda=? AND status='belum_dibayar'", [$int('id_denda')])) {
            flash('success', 'Denda ditandai lunas.');
        }
        break;

    case 'denda_hapus':
        require_staf('admin');
        db_exec("DELETE FROM denda WHERE id_denda = ?", [$int('id_denda')]);
        flash('success', 'Denda dihapus.');
        break;

    // =============================== KUNJUNGAN ===============================
    case 'kunjungan_tambah':
        $ida = $int('id_anggota') ?: null;
        $nama = $str('nama_tamu', 100) ?: null;
        if (!$ida && !$nama) { flash('error', 'Pilih anggota atau isi nama tamu.'); break; }
        db_exec("INSERT INTO kunjungan (id_anggota, nama_tamu, keperluan) VALUES (?,?,?)", [$ida, $ida ? null : $nama, $str('keperluan', 150) ?: 'Membaca']);
        flash('success', 'Kunjungan dicatat.');
        break;

    case 'kunjungan_hapus':
        db_exec("DELETE FROM kunjungan WHERE id_kunjungan = ?", [$int('id_kunjungan')]);
        flash('success', 'Catatan kunjungan dihapus.');
        break;

    // =============================== PENGATURAN & STAF ===============================
    case 'pengaturan_simpan':
        require_staf('admin');
        foreach ((array)($_POST['set'] ?? []) as $k => $v) {
            db_exec("UPDATE pengaturan SET nilai = ? WHERE kunci = ?", [(string)max(0, (int)$v), (string)$k]);
        }
        flash('success', 'Pengaturan disimpan.');
        break;

    case 'staf_simpan':
        require_staf('admin');
        $idu = $int('id_user');
        $username = $str('username', 50); $nama = $str('nama', 100);
        $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'pustakawan';
        $pw = (string)($_POST['password'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9_.]{3,50}$/', $username) || $nama === '') { flash('error', 'Username (3-50 huruf/angka) dan nama wajib diisi.'); break; }
        if (db_value("SELECT COUNT(*) FROM users WHERE username = ? AND id_user <> ?", [$username, $idu])) { flash('error', 'Username sudah dipakai.'); break; }
        if (!$idu && $pw === '') { flash('error', 'Password wajib diisi untuk akun baru.'); break; }
        if ($pw !== '' && ($err = validasi_password($pw, $pw))) { flash('error', $err); break; }
        if ($idu === $uid && $role !== 'admin') { flash('error', 'Anda tidak bisa menurunkan peran akun sendiri.'); break; }
        if ($idu) {
            db_exec("UPDATE users SET username=?, nama=?, role=? WHERE id_user=?", [$username, $nama, $role, $idu]);
            if ($pw !== '') db_exec("UPDATE users SET password=? WHERE id_user=?", [hash_password($pw), $idu]);
            flash('success', 'Akun ' . $nama . ' diperbarui.');
        } else {
            db_exec("INSERT INTO users (username, nama, password, role) VALUES (?,?,?,?)", [$username, $nama, hash_password($pw), $role]);
            flash('success', 'Akun ' . $nama . ' dibuat.');
        }
        break;

    case 'staf_status':
        require_staf('admin');
        $idu = $int('id_user');
        if ($idu !== $uid) {
            db_exec("UPDATE users SET status = IF(status='aktif','nonaktif','aktif') WHERE id_user = ?", [$idu]);
            flash('success', 'Status akun diperbarui.');
        }
        break;

    case 'profil_simpan':
        $nama = $str('nama', 100);
        if ($nama !== '') {
            db_exec("UPDATE users SET nama = ? WHERE id_user = ?", [$nama, $uid]);
            $_SESSION['staf']['nama'] = $nama;
            flash('success', 'Nama diperbarui.');
        }
        break;

    case 'ganti_password':
        $u = db_one("SELECT password FROM users WHERE id_user = ?", [$uid]);
        $baru = (string)($_POST['password_baru'] ?? '');
        if (!password_verify((string)($_POST['password_lama'] ?? ''), $u['password'])) flash('error', 'Password lama salah.');
        elseif ($err = validasi_password($baru, (string)($_POST['konfirmasi'] ?? ''))) flash('error', $err);
        else {
            db_exec("UPDATE users SET password = ? WHERE id_user = ?", [hash_password($baru), $uid]);
            session_regenerate_id(true);
            flash('success', 'Password berhasil diganti.');
        }
        break;

    default:
        flash('error', 'Aksi tidak dikenal.');
    }
} catch (RuntimeException $ex) {
    try { $conn->rollback(); } catch (Throwable $t) {}
    flash('error', $ex->getMessage());
}

redirect($back);
