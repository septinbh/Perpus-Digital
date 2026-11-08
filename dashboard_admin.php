<?php
/**
 * dashboard_admin.php — halaman utama pustakawan (router ?page=...).
 */
require 'config.php';
$staf = require_staf();

$badge = db_one("SELECT
    (SELECT COUNT(*) FROM peminjaman WHERE status = 'menunggu') AS menunggu,
    (SELECT COUNT(*) FROM peminjaman WHERE status = 'diajukan_kembali') AS kembali,
    (SELECT COUNT(*) FROM peminjaman WHERE status = 'dipinjam' AND tanggal_jatuh_tempo < CURDATE()) AS terlambat,
    (SELECT COUNT(*) FROM denda WHERE status = 'belum_dibayar') AS denda");
$badge = array_map('intval', $badge);

$notifs = [];
if ($badge['menunggu']) $notifs[] = ['icon' => 'fa-hourglass-half', 'url' => '?page=peminjaman', 'text' => $badge['menunggu'] . ' pengajuan peminjaman menunggu persetujuan'];
if ($badge['kembali'])  $notifs[] = ['icon' => 'fa-rotate-left', 'url' => '?page=pengembalian', 'text' => $badge['kembali'] . ' siswa mengajukan pengembalian buku'];
if ($badge['terlambat']) $notifs[] = ['icon' => 'fa-triangle-exclamation', 'url' => '?page=pengembalian#terlambat', 'text' => $badge['terlambat'] . ' peminjaman sudah lewat jatuh tempo'];
$habis = (int)db_value("SELECT COUNT(*) FROM buku WHERE stok = 0");
if ($habis) $notifs[] = ['icon' => 'fa-box-open', 'url' => '?page=buku&stok=habis', 'text' => "$habis judul buku stoknya habis"];

$menu = [
    'home'         => ['Dashboard', 'fa-gauge-high'],
    'g1'           => ['-', 'Transaksi'],
    'peminjaman'   => ['Peminjaman', 'fa-handshake', $badge['menunggu']],
    'pengembalian' => ['Pengembalian', 'fa-rotate-left', $badge['kembali']],
    'denda'        => ['Denda', 'fa-money-bill-wave', $badge['denda']],
    'g2'           => ['-', 'Data'],
    'buku'         => ['Kelola Buku', 'fa-book'],
    'anggota'      => ['Anggota', 'fa-user-graduate'],
    'pengunjung'   => ['Pengunjung', 'fa-users'],
    'g3'           => ['-', 'Lainnya'],
    'laporan'      => ['Laporan', 'fa-chart-column'],
    'pengaturan'   => ['Pengaturan', 'fa-gear'],
    'profil'       => ['Profil', 'fa-id-badge'],
];
$page = $_GET['page'] ?? 'home';
if (!isset($menu[$page]) || $menu[$page][0] === '-') $page = 'home';

$base   = 'dashboard_admin.php';
$title  = $menu[$page][0];
$user   = ['nama' => $staf['nama'], 'sub' => ucfirst($staf['role']), 'foto' => 'assets/img/profil.jpg'];
$search = ['placeholder' => 'Cari buku (judul, penulis, kode)...', 'page' => 'buku'];
$menuUser = [['Profil & Password', '?page=profil', 'fa-id-badge'], ['Pengaturan', '?page=pengaturan', 'fa-gear']];

/** Judul halaman + tombol aksi kanan. */
function page_head(string $judul, string $sub = '', string $aksi = ''): void
{
    echo '<div class="mb-6 flex flex-wrap items-end justify-between gap-3"><div><h1 class="text-2xl font-bold text-ink">' . e($judul) . '</h1>'
        . ($sub ? '<p class="text-sm text-soft">' . $sub . '</p>' : '') . '</div>' . $aksi . '</div>';
}

/** Input filter cepat untuk tabel. */
function filter_box(string $target, string $ph = 'Saring data...'): string
{
    return '<div class="relative w-full sm:w-72"><i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-soft"></i>'
        . '<input class="input pl-10" placeholder="' . e($ph) . '" data-filter="' . e($target) . '"></div>';
}

include 'includes/layout_start.php';
include "pages/admin/$page.php";
include 'includes/layout_end.php';
