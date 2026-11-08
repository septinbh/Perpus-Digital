-- =====================================================================
--  Database Perpustakaan Digital SMKN 1 Kamal  (versi revisi)
--  Import lewat phpMyAdmin: buat/abaikan database lalu Import file ini.
--  PERINGATAN: file ini MENGHAPUS tabel lama lalu membuat ulang.
-- =====================================================================
--  Akun bawaan (semua password disimpan sebagai hash bcrypt):
--    Pustakawan : username admin      / password admin123
--    Pustakawan : username pustakawan / password pustaka123
--    Siswa      : NIS atau Nomor Anggota / password awal = NIS
--                 (contoh: NIS 24011 -> password 24011)
--                 Siswa WAJIB mengganti password saat login pertama.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `library` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `library`;

SET FOREIGN_KEY_CHECKS = 0;
SET time_zone = "+07:00";
SET NAMES utf8mb4;

DROP TABLE IF EXISTS `kunjungan`;
DROP TABLE IF EXISTS `denda`;
DROP TABLE IF EXISTS `pengembalian`;
DROP TABLE IF EXISTS `peminjaman`;
DROP TABLE IF EXISTS `buku`;
DROP TABLE IF EXISTS `anggota`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `pengaturan`;

-- ---------------------------------------------------------------------
-- users  : HANYA untuk staf (pustakawan / admin). Siswa ada di `anggota`.
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id_user`     INT(11) NOT NULL AUTO_INCREMENT,
  `username`    VARCHAR(50)  NOT NULL,
  `nama`        VARCHAR(100) NOT NULL,
  `password`    VARCHAR(255) NOT NULL COMMENT 'hash password_hash()',
  `role`        ENUM('admin','pustakawan') NOT NULL DEFAULT 'pustakawan',
  `status`      ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `last_login`  DATETIME NULL DEFAULT NULL,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id_user`,`username`,`nama`,`password`,`role`) VALUES
(1,'admin','Administrator','$2y$10$ir5bH2TvfpnkgusWeMrXVua25Sa3EEeLRwDVAUjoX0TPYsOtS3T4O','admin'),
(2,'pustakawan','Ibu Pustakawan','$2y$10$PalVMx3zlLTPfdTHoQzdb.0XMV.VbWc7xy2INnXaFKwuUh1DSgbWm','pustakawan');

-- ---------------------------------------------------------------------
-- anggota : data siswa (login pakai NIS atau Nomor Anggota)
-- ---------------------------------------------------------------------
CREATE TABLE `anggota` (
  `id_anggota`           INT(11) NOT NULL AUTO_INCREMENT,
  `nomor_anggota`        VARCHAR(30)  NOT NULL,
  `nis`                  VARCHAR(20)  NOT NULL,
  `nama`                 VARCHAR(100) NOT NULL,
  `kelas`                VARCHAR(20)  NOT NULL,
  `jurusan`              VARCHAR(50)  NOT NULL,
  `no_hp`                VARCHAR(20)  NULL DEFAULT NULL,
  `password`             VARCHAR(255) NOT NULL COMMENT 'hash password_hash()',
  `harus_ganti_password` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = wajib ganti saat login',
  `status`               ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `last_login`           DATETIME NULL DEFAULT NULL,
  `created_at`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_anggota`),
  UNIQUE KEY `uk_nomor_anggota` (`nomor_anggota`),
  UNIQUE KEY `uk_nis` (`nis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `anggota` (`id_anggota`,`nomor_anggota`,`nis`,`nama`,`kelas`,`jurusan`,`no_hp`,`password`,`harus_ganti_password`) VALUES
(1,'A001','240441100089','Budi','XI RPL 1','RPL','081234567801','$2y$10$lJ3rgItdk41jyWBzdsh5h.ULDmIqmVTWOMsvAgmvoxMNgowdKeste',1),
(2,'A002','24011','Septiana Naibaho','XII RPL 1','RPL','081234567802','$2y$10$KTsGxm.OTyQSHYP8sqReNOV87A.WIQkhc3aLUBBnbWNZqNT8A5fJe',1),
(3,'A003','24012','Isti','XI DKV 1','DKV','081234567803','$2y$10$NDKW5vzj2U43BRRb64P/semxlSg/Y1V1d94PQTVCV.4MAco010Uvu',1),
(4,'A004','24013','Rizky Pratama','X AKL 2','AKL',NULL,'$2y$10$W1eXHu8HY88xyOLnAWLeJOT8qW6VceA.dKCBgMwDhqhHNK5x/g12a',1);

-- ---------------------------------------------------------------------
-- buku : `stok` = jumlah eksemplar yang TERSEDIA di rak saat ini
-- ---------------------------------------------------------------------
CREATE TABLE `buku` (
  `id_buku`      INT(11) NOT NULL AUTO_INCREMENT,
  `kode_buku`    VARCHAR(50)  NOT NULL,
  `judul`        VARCHAR(200) NOT NULL,
  `penulis`      VARCHAR(150) NULL DEFAULT NULL,
  `penerbit`     VARCHAR(150) NULL DEFAULT NULL,
  `tahun_terbit` YEAR(4)      NULL DEFAULT NULL,
  `kategori`     VARCHAR(50)  NOT NULL DEFAULT 'Umum',
  `rak`          VARCHAR(20)  NULL DEFAULT NULL,
  `deskripsi`    TEXT         NULL,
  `stok`         INT(11)      NOT NULL DEFAULT 1,
  `cover`        VARCHAR(255) NULL DEFAULT NULL COMMENT 'path lokal / URL',
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_buku`),
  UNIQUE KEY `uk_kode_buku` (`kode_buku`),
  KEY `idx_kategori` (`kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `buku` (`id_buku`,`kode_buku`,`judul`,`penulis`,`penerbit`,`tahun_terbit`,`kategori`,`rak`,`stok`,`cover`,`deskripsi`) VALUES
(21,'SMK001','Pemrograman Python Dasar','Budi Santoso','Graha Ilmu',2020,'Teknologi','A1',10,'assets/img/cover/python.jpg','Panduan belajar Python dari nol: variabel, perulangan, fungsi, hingga proyek kecil.'),
(22,'SMK002','Desain Grafis Kreatif','Rina Pratiwi','Elex Media',2021,'Desain','B1',8,NULL,'Dasar-dasar desain grafis, tipografi, warna dan layout untuk pemula.'),
(23,'SMK003','Akuntansi Dasar','Siti Aminah','Salemba Empat',2019,'Akuntansi','C1',12,NULL,'Konsep persamaan dasar akuntansi, jurnal umum, buku besar dan neraca saldo.'),
(24,'SMK004','Matematika Teknik','Andi Wijaya','Erlangga',2018,'Pelajaran','D1',7,NULL,'Matematika terapan untuk siswa SMK bidang teknik.'),
(25,'SMK005','Bahasa Inggris Komunikatif','Maria Kusuma','Pustaka Pelajar',2021,'Bahasa','D2',15,NULL,'Latihan percakapan bahasa Inggris sehari-hari dan dunia kerja.'),
(26,'SMK006','Administrasi Perkantoran','Dedi Rahman','Graha Ilmu',2020,'Umum','C2',9,NULL,'Tata kelola surat, arsip dan pelayanan administrasi kantor.'),
(27,'SMK007','Laskar Pelangi','Andrea Hirata','Bentang Pustaka',2005,'Fiksi','E1',6,NULL,'Kisah persahabatan sepuluh anak Belitung yang penuh semangat belajar.'),
(28,'SMK008','Dilan 1990','Pidi Baiq','Pastel Books',2014,'Fiksi','E1',8,NULL,'Novel remaja berlatar Bandung tahun 1990.'),
(29,'SMK009','Motivasi & Pengembangan Diri','Robin Sharma','Gramedia',2016,'Pengembangan Diri','F1',10,NULL,'Kebiasaan kecil yang membawa perubahan besar.'),
(30,'SMK010','Steve Jobs','Walter Isaacson','Bantam Books',2011,'Biografi','F2',5,NULL,'Biografi pendiri Apple.'),
(31,'SMK011','Sejarah Indonesia','John Smith','Erlangga',2017,'Pelajaran','D3',7,NULL,'Perjalanan sejarah Indonesia dari masa kerajaan hingga reformasi.'),
(32,'SMK012','Fisika Dasar','Eko Prasetyo','Salemba Empat',2020,'Pelajaran','D3',9,NULL,'Konsep gerak, gaya, energi dan listrik.'),
(33,'SMK013','Kimia Dasar','Rini Handayani','Graha Ilmu',2019,'Pelajaran','D4',8,'assets/img/cover/kimia.jpg','Struktur atom, ikatan kimia dan stoikiometri.'),
(34,'SMK014','Kamus Inggris-Indonesia','Tim Kamus','Pustaka Pelajar',2022,'Bahasa','D2',12,'assets/img/cover/kamus.jpg','Kamus dua arah lengkap dengan contoh kalimat.'),
(35,'SMK015','5 cm','Donny Dhirgantoro','Bentang Pustaka',2005,'Fiksi','E2',0,'assets/img/cover/5cm.jpg','Lima sahabat mendaki Mahameru demi mimpi mereka.'),
(36,'SMK016','Pemrograman Web HTML & CSS','Agus Santoso','Elex Media',2021,'Teknologi','A1',10,NULL,'Membangun halaman web modern dengan HTML5 dan CSS3.'),
(37,'SMK017','Desain UI/UX','Septiana Naibaho','Gramedia',2022,'Desain','B1',7,'assets/img/cover/ui-ux.jpg','Merancang antarmuka yang mudah dipakai dan enak dilihat.'),
(38,'SMK018','Public Speaking','Andini Sari','Pustaka Pelajar',2020,'Pengembangan Diri','F1',8,'assets/img/cover/public-speaking.jpg','Teknik berbicara percaya diri di depan umum.'),
(39,'SMK019','Entrepreneurship Dasar','Budi Setiawan','Erlangga',2019,'Kewirausahaan','C3',5,'assets/img/cover/entrepreneurship.jpg','Memulai usaha dari ide hingga pemasaran.'),
(40,'SMK020','Harry Potter 1','J.K. Rowling','Bloomsbury',1997,'Fiksi','E2',6,'assets/img/cover/harry-potter.jpg','Petualangan pertama Harry di Hogwarts.');

-- ---------------------------------------------------------------------
-- peminjaman : alur status
--   menunggu -> dipinjam -> diajukan_kembali -> dikembalikan
--   menunggu -> ditolak / dibatalkan
-- ---------------------------------------------------------------------
CREATE TABLE `peminjaman` (
  `id_peminjaman`       INT(11) NOT NULL AUTO_INCREMENT,
  `id_anggota`          INT(11) NOT NULL,
  `id_buku`             INT(11) NOT NULL,
  `tanggal_pengajuan`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tanggal_pinjam`      DATE NULL DEFAULT NULL,
  `tanggal_jatuh_tempo` DATE NULL DEFAULT NULL,
  `status`              ENUM('menunggu','dipinjam','diajukan_kembali','dikembalikan','ditolak','dibatalkan') NOT NULL DEFAULT 'menunggu',
  `jumlah_perpanjang`   TINYINT(3) NOT NULL DEFAULT 0,
  `catatan`             VARCHAR(255) NULL DEFAULT NULL,
  `diproses_oleh`       INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id_peminjaman`),
  KEY `idx_anggota` (`id_anggota`),
  KEY `idx_buku` (`id_buku`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_pinjam_anggota` FOREIGN KEY (`id_anggota`) REFERENCES `anggota` (`id_anggota`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pinjam_buku`    FOREIGN KEY (`id_buku`)    REFERENCES `buku` (`id_buku`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pinjam_user`    FOREIGN KEY (`diproses_oleh`) REFERENCES `users` (`id_user`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- data contoh (tanggal relatif terhadap hari import)
INSERT INTO `peminjaman` (`id_peminjaman`,`id_anggota`,`id_buku`,`tanggal_pengajuan`,`tanggal_pinjam`,`tanggal_jatuh_tempo`,`status`,`diproses_oleh`) VALUES
(1,2,36,NOW() - INTERVAL 4 DAY, CURDATE() - INTERVAL 4 DAY, CURDATE() + INTERVAL 3 DAY,'dipinjam',1),
(2,1,37,NOW() - INTERVAL 10 DAY,CURDATE() - INTERVAL 10 DAY,CURDATE() - INTERVAL 3 DAY,'dipinjam',1),
(3,3,21,NOW() - INTERVAL 1 HOUR,NULL,NULL,'menunggu',NULL),
(4,2,27,NOW() - INTERVAL 20 DAY,CURDATE() - INTERVAL 20 DAY,CURDATE() - INTERVAL 13 DAY,'dikembalikan',1),
(5,2,40,NOW() - INTERVAL 30 DAY,CURDATE() - INTERVAL 30 DAY,CURDATE() - INTERVAL 23 DAY,'dikembalikan',2);

-- ---------------------------------------------------------------------
-- pengembalian
-- ---------------------------------------------------------------------
CREATE TABLE `pengembalian` (
  `id_pengembalian` INT(11) NOT NULL AUTO_INCREMENT,
  `id_peminjaman`   INT(11) NOT NULL,
  `tanggal_kembali` DATE NOT NULL,
  `terlambat_hari`  INT(11) NOT NULL DEFAULT 0,
  `kondisi`         ENUM('baik','rusak','hilang') NOT NULL DEFAULT 'baik',
  `denda`           INT(11) NOT NULL DEFAULT 0,
  `diproses_oleh`   INT(11) NULL DEFAULT NULL,
  `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pengembalian`),
  UNIQUE KEY `uk_peminjaman` (`id_peminjaman`),
  CONSTRAINT `fk_kembali_pinjam` FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman` (`id_peminjaman`) ON DELETE CASCADE,
  CONSTRAINT `fk_kembali_user`   FOREIGN KEY (`diproses_oleh`) REFERENCES `users` (`id_user`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `pengembalian` (`id_peminjaman`,`tanggal_kembali`,`terlambat_hari`,`kondisi`,`denda`,`diproses_oleh`,`created_at`) VALUES
(4, CURDATE() - INTERVAL 11 DAY, 2, 'baik', 2000, 1, NOW() - INTERVAL 11 DAY),
(5, CURDATE() - INTERVAL 24 DAY, 0, 'baik', 0, 2, NOW() - INTERVAL 24 DAY);

-- ---------------------------------------------------------------------
-- denda : terhubung ke anggota & peminjaman (bukan ke users lagi)
-- ---------------------------------------------------------------------
CREATE TABLE `denda` (
  `id_denda`      INT(11) NOT NULL AUTO_INCREMENT,
  `id_peminjaman` INT(11) NOT NULL,
  `id_anggota`    INT(11) NOT NULL,
  `jumlah`        INT(11) NOT NULL DEFAULT 0,
  `keterangan`    VARCHAR(255) NULL DEFAULT NULL,
  `status`        ENUM('belum_dibayar','lunas') NOT NULL DEFAULT 'belum_dibayar',
  `tanggal_bayar` DATETIME NULL DEFAULT NULL,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_denda`),
  KEY `idx_denda_anggota` (`id_anggota`),
  CONSTRAINT `fk_denda_pinjam`  FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman` (`id_peminjaman`) ON DELETE CASCADE,
  CONSTRAINT `fk_denda_anggota` FOREIGN KEY (`id_anggota`)    REFERENCES `anggota` (`id_anggota`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `denda` (`id_peminjaman`,`id_anggota`,`jumlah`,`keterangan`,`status`) VALUES
(4, 2, 2000, 'Terlambat 2 hari', 'belum_dibayar');

-- ---------------------------------------------------------------------
-- kunjungan : buku tamu / pengunjung perpustakaan
-- ---------------------------------------------------------------------
CREATE TABLE `kunjungan` (
  `id_kunjungan` INT(11) NOT NULL AUTO_INCREMENT,
  `id_anggota`   INT(11) NULL DEFAULT NULL,
  `nama_tamu`    VARCHAR(100) NULL DEFAULT NULL,
  `keperluan`    VARCHAR(150) NOT NULL DEFAULT 'Membaca',
  `waktu`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_kunjungan`),
  KEY `idx_waktu` (`waktu`),
  CONSTRAINT `fk_kunjungan_anggota` FOREIGN KEY (`id_anggota`) REFERENCES `anggota` (`id_anggota`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `kunjungan` (`id_anggota`,`nama_tamu`,`keperluan`,`waktu`) VALUES
(1,NULL,'Login aplikasi', NOW() - INTERVAL 2 HOUR),
(3,NULL,'Login aplikasi', NOW() - INTERVAL 1 HOUR),
(NULL,'Pak Hendra (Guru BK)','Mencari referensi', NOW() - INTERVAL 30 MINUTE),
(2,NULL,'Login aplikasi', NOW() - INTERVAL 1 DAY);

-- ---------------------------------------------------------------------
-- pengaturan : aturan perpustakaan yang bisa diubah dari dashboard
-- ---------------------------------------------------------------------
CREATE TABLE `pengaturan` (
  `kunci` VARCHAR(50)  NOT NULL,
  `nilai` VARCHAR(255) NOT NULL,
  `label` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`kunci`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `pengaturan` (`kunci`,`nilai`,`label`) VALUES
('lama_pinjam','7','Lama peminjaman (hari)'),
('maks_pinjam','3','Maksimal buku dipinjam per siswa'),
('maks_perpanjang','1','Maksimal perpanjangan per peminjaman'),
('denda_per_hari','1000','Denda keterlambatan per hari (Rp)'),
('denda_rusak','25000','Denda buku rusak (Rp)'),
('denda_hilang','75000','Denda buku hilang (Rp)'),
('blokir_jika_denda','1','Blokir pinjam jika ada denda belum dibayar (1=ya, 0=tidak)');

-- buku yang sedang dipinjam di data contoh: kurangi stok agar konsisten
UPDATE `buku` SET `stok` = `stok` - 1 WHERE `id_buku` IN (36,37);

SET FOREIGN_KEY_CHECKS = 1;
