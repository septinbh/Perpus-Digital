<?php
/**
 * aksi_siswa.php — semua aksi POST dari dashboard siswa.
 */
require 'config.php';
$s  = require_siswa();
$id = $s['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('dashboard_siswa.php');
verify_csrf();

$aksi = $_POST['aksi'] ?? '';
$idp  = (int)($_POST['id_peminjaman'] ?? 0);
$back = back_url('dashboard_siswa.php');

/** Ambil peminjaman milik siswa ini dengan status tertentu. */
function pinjaman_saya(int $idp, int $id, string $status): ?array
{
    return db_one("SELECT p.*, b.judul FROM peminjaman p JOIN buku b USING(id_buku)
                   WHERE p.id_peminjaman = ? AND p.id_anggota = ? AND p.status = ?", [$idp, $id, $status]);
}

switch ($aksi) {

    // ---------------- Ajukan peminjaman ----------------
    case 'pinjam':
        $idb  = (int)($_POST['id_buku'] ?? 0);
        $buku = db_one("SELECT * FROM buku WHERE id_buku = ?", [$idb]);
        $maks = (int)setting('maks_pinjam', 3);
        $aktif = (int)db_value("SELECT COUNT(*) FROM peminjaman WHERE id_anggota = ? AND status IN ('menunggu','dipinjam','diajukan_kembali')", [$id]);
        $denda = (int)db_value("SELECT COALESCE(SUM(jumlah),0) FROM denda WHERE id_anggota = ? AND status = 'belum_dibayar'", [$id]);
        $dobel = (int)db_value("SELECT COUNT(*) FROM peminjaman WHERE id_anggota = ? AND id_buku = ? AND status IN ('menunggu','dipinjam','diajukan_kembali')", [$id, $idb]);
        $telat = (int)db_value("SELECT COUNT(*) FROM peminjaman WHERE id_anggota = ? AND status = 'dipinjam' AND tanggal_jatuh_tempo < CURDATE()", [$id]);

        if (!$buku)                 flash('error', 'Buku tidak ditemukan.');
        elseif ($buku['stok'] < 1)  flash('error', 'Maaf, stok buku "' . $buku['judul'] . '" sedang habis.');
        elseif ($dobel)             flash('warning', 'Anda sudah mengajukan / meminjam buku ini.');
        elseif ($aktif >= $maks)    flash('error', "Batas peminjaman tercapai (maksimal $maks buku sekaligus).");
        elseif ($telat)             flash('error', 'Kembalikan dulu buku yang terlambat sebelum meminjam lagi.');
        elseif ($denda && setting('blokir_jika_denda', '1') === '1') flash('error', 'Anda masih memiliki denda ' . rupiah($denda) . '. Silakan lunasi terlebih dahulu.');
        else {
            db_exec("INSERT INTO peminjaman (id_anggota, id_buku, status) VALUES (?, ?, 'menunggu')", [$id, $idb]);
            flash('success', 'Pengajuan "' . $buku['judul'] . '" terkirim! Tunggu persetujuan pustakawan.');
            redirect('dashboard_siswa.php?page=pengajuan');
        }
        break;

    // ---------------- Batalkan pengajuan ----------------
    case 'batal':
        if ($p = pinjaman_saya($idp, $id, 'menunggu')) {
            db_exec("UPDATE peminjaman SET status = 'dibatalkan', catatan = 'Dibatalkan oleh siswa' WHERE id_peminjaman = ?", [$idp]);
            flash('success', 'Pengajuan "' . $p['judul'] . '" dibatalkan.');
        } else flash('error', 'Pengajuan tidak ditemukan atau sudah diproses.');
        break;

    // ---------------- Ajukan pengembalian ----------------
    case 'ajukan_kembali':
        if ($p = pinjaman_saya($idp, $id, 'dipinjam')) {
            db_exec("UPDATE peminjaman SET status = 'diajukan_kembali' WHERE id_peminjaman = ?", [$idp]);
            $telat = hari_terlambat($p['tanggal_jatuh_tempo']);
            flash('success', 'Pengembalian "' . $p['judul'] . '" diajukan. Serahkan buku ke pustakawan.'
                . ($telat ? ' Perkiraan denda: ' . rupiah($telat * (int)setting('denda_per_hari')) . '.' : ''));
        } else flash('error', 'Data peminjaman tidak ditemukan.');
        break;

    case 'batal_kembali':
        if ($p = pinjaman_saya($idp, $id, 'diajukan_kembali')) {
            db_exec("UPDATE peminjaman SET status = 'dipinjam' WHERE id_peminjaman = ?", [$idp]);
            flash('info', 'Pengajuan pengembalian dibatalkan.');
        }
        break;

    // ---------------- Perpanjang ----------------
    case 'perpanjang':
        $p = pinjaman_saya($idp, $id, 'dipinjam');
        $maksP = (int)setting('maks_perpanjang', 1);
        if (!$p) flash('error', 'Data peminjaman tidak ditemukan.');
        elseif (hari_terlambat($p['tanggal_jatuh_tempo']) > 0) flash('error', 'Peminjaman yang sudah terlambat tidak bisa diperpanjang.');
        elseif ($p['jumlah_perpanjang'] >= $maksP) flash('error', "Batas perpanjangan ($maksP kali) sudah tercapai.");
        else {
            $hari = (int)setting('lama_pinjam', 7);
            db_exec("UPDATE peminjaman SET tanggal_jatuh_tempo = DATE_ADD(tanggal_jatuh_tempo, INTERVAL ? DAY), jumlah_perpanjang = jumlah_perpanjang + 1 WHERE id_peminjaman = ?", [$hari, $idp]);
            $baru = db_value("SELECT tanggal_jatuh_tempo FROM peminjaman WHERE id_peminjaman = ?", [$idp]);
            flash('success', 'Berhasil diperpanjang. Jatuh tempo baru: ' . tgl($baru) . '.');
        }
        break;

    // ---------------- Profil ----------------
    case 'update_profil':
        $hp = preg_replace('/[^0-9+]/', '', $_POST['no_hp'] ?? '');
        db_exec("UPDATE anggota SET no_hp = ? WHERE id_anggota = ?", [$hp ?: null, $id]);
        flash('success', 'Kontak berhasil diperbarui.');
        break;

    case 'ganti_password':
        $a = db_one("SELECT password, nis FROM anggota WHERE id_anggota = ?", [$id]);
        $baru = (string)($_POST['password_baru'] ?? '');
        if (!password_verify((string)($_POST['password_lama'] ?? ''), $a['password'])) flash('error', 'Password lama salah.');
        elseif ($err = validasi_password($baru, (string)($_POST['konfirmasi'] ?? ''))) flash('error', $err);
        elseif ($baru === $a['nis']) flash('error', 'Password tidak boleh sama dengan NIS.');
        else {
            db_exec("UPDATE anggota SET password = ?, harus_ganti_password = 0 WHERE id_anggota = ?", [hash_password($baru), $id]);
            session_regenerate_id(true);
            flash('success', 'Password berhasil diganti.');
        }
        redirect('dashboard_siswa.php?page=profil');

    default:
        flash('error', 'Aksi tidak dikenal.');
}
redirect($back);
