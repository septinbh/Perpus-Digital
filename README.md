# Perpus Digital SMKN 1 Kamal (versi revisi)

## Cara pasang (XAMPP / Laragon)
1. Salin folder `library` ke `htdocs` (XAMPP) atau `www` (Laragon).
2. Buka phpMyAdmin, lalu **Import** file `library.sql`. Database `library` dibuat otomatis.
   File ini menghapus tabel lama dan membuat yang baru.
3. Kalau perlu, sesuaikan user/password MySQL di `config.php`.
4. Buka `http://localhost/library/` (splash, lalu pilih login).
5. Pastikan folder `uploads/cover` bisa ditulisi, karena cover buku diunggah ke sana.

Aplikasi berjalan **tanpa internet**: Tailwind, Font Awesome, dan font Poppins sudah disertakan di folder `assets`.

## Akun bawaan
| Peran | Login | Password |
|---|---|---|
| Admin | `admin` | `admin123` |
| Pustakawan | `pustakawan` | `pustaka123` |
| Siswa | NIS **atau** Nomor Anggota (mis. `24011` / `A002`) | NIS-nya (mis. `24011`) |

Siswa **wajib mengganti password** saat login pertama. Ganti juga password admin lewat menu *Profil*.

## Sistem password (satu aturan untuk semua)
- Password disimpan dengan `password_hash()` (bcrypt) dan dicek dengan `password_verify()`. Semua kodenya ada di `includes/auth.php`.
- File lama `generate_hash.php`, `generate_admin_hash.php`, `hash.php`, `password_hash.php` **sudah dihapus**, jadi hash tidak perlu dibuat manual lagi.
- Siswa baru otomatis mendapat password = NIS dan wajib menggantinya. Pustakawan bisa **reset password** siswa ke NIS dari menu *Anggota*.
- Aturan password baru: minimal 6 karakter, berisi huruf dan angka.
- Setelah 5 kali salah, login dikunci 60 detik. Sesi otomatis berakhir setelah 2 jam tidak aktif.

## Alur
**Siswa:** login → (ganti password jika masih bawaan) → cari buku di Katalog → *Ajukan peminjaman* (status Menunggu) → pustakawan menyetujui → buku diambil (Dipinjam) → bisa *Perpanjang* atau *Ajukan pengembalian* → pustakawan mengonfirmasi → denda (jika ada) muncul di menu Denda.

**Pustakawan:** login → Dashboard (pengajuan, keterlambatan, statistik) → Peminjaman (setujui/tolak, atau *Pinjamkan langsung*) → Pengembalian (kondisi baik/rusak/hilang, denda dihitung otomatis) → Denda (tandai lunas, cetak struk).

## Mengubah tampilan
Tailwind sudah di-build ke `assets/css/tailwind.css`. Kalau Anda menambah class Tailwind **baru**:
```
npm install
npm run build
```
Gaya tambahan (sidebar, kartu, modal, toast) ada di `assets/css/app.css`. Interaksi JS (modal, toast, konfirmasi, filter, tab, lihat password, dll) ada di `assets/js/app.js`.

## Struktur
```
config.php            koneksi DB + session
includes/             fungsi, auth, layout
pages/siswa/*.php     halaman dashboard siswa
pages/admin/*.php     halaman dashboard pustakawan
aksi_siswa.php        proses form siswa
aksi_admin.php        proses form pustakawan
library.sql           database baru
```
