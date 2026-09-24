# LaporanSidak — Versi MySQL (XAMPP)

Aplikasi ini adalah hasil konversi `InputLaporanSidakHr_fix_nw (5)(1).html` dari
penyimpanan **localStorage browser** menjadi **MySQL/MariaDB melalui backend PHP**.

Tampilan (HTML/CSS), alur pemakaian, filter, status, export Excel, dan format
laporan **tidak diubah** — hanya sumber datanya yang diganti.

## Struktur Folder

```
laporan_sidak/
├── index.php                    <- Halaman utama aplikasi (tampilan sama seperti file asli)
├── migrate.php                  <- Halaman bantu migrasi data lama (terpisah dari UI utama)
├── migrate_export_console.js    <- Script untuk dijalankan di console browser (di file HTML lama)
├── README.md
├── api/
│   ├── db.php                   <- Koneksi PDO ke MySQL
│   ├── helpers.php               <- Fungsi bantu (JSON response, validasi upload foto, dll)
│   ├── sidak_list.php            <- GET: daftar semua data sidak + foto
│   ├── sidak_get.php             <- GET: satu data sidak berdasarkan id
│   ├── sidak_create.php          <- POST: tambah data sidak baru (+ upload foto)
│   ├── sidak_update.php          <- POST: update data sidak (+ tambah/hapus foto)
│   ├── sidak_delete.php          <- POST: hapus data sidak (+ foto terkait)
│   ├── photo_delete.php          <- POST: hapus satu foto secara mandiri
│   ├── settings_get.php          <- GET: ambil pengaturan laporan
│   ├── settings_update.php       <- POST: simpan pengaturan laporan
│   └── migrate_import.php        <- POST: proses impor data hasil ekspor localStorage lama
├── uploads/
│   └── sidak/                    <- Folder fisik penyimpanan foto (dibuat otomatis jika belum ada)
└── database/
    └── laporan_sidak.sql         <- Skrip SQL lengkap (buat database + tabel + data default)
```

## Cara Menjalankan di XAMPP (dari Awal)

### 1. Salin folder ke htdocs
Salin seluruh folder `laporan_sidak` ke:
```
C:\xampp\htdocs\laporan_sidak\
```
Pastikan strukturnya menjadi `C:\xampp\htdocs\laporan_sidak\index.php`, dst.

### 2. Jalankan Apache & MySQL
Buka **XAMPP Control Panel**, klik **Start** pada modul **Apache** dan **MySQL**.

### 3. Import database lewat phpMyAdmin
1. Buka `http://localhost/phpmyadmin`
2. Klik tab **Import**
3. Pilih file `database/laporan_sidak.sql` (dari folder project)
4. Klik **Go / Kirim**
5. Database `laporan_sidak` beserta tabel `sidak`, `sidak_photos`, dan `settings`
   akan otomatis terbuat, lengkap dengan pengaturan default.

### 4. Pastikan folder upload bisa ditulis
Folder `uploads/sidak/` akan dibuat otomatis oleh PHP saat foto pertama diupload,
namun jika ingin membuatnya manual pastikan permission folder mengizinkan PHP menulis
file (di Windows/XAMPP biasanya tidak ada masalah permission).

### 5. Buka aplikasi
Akses di browser:
```
http://localhost/laporan_sidak/
```

## Cara Testing

1. **Tambah data**: klik "Tambah Data Sidak" → isi form → upload 1–2 foto → klik
   Simpan. Data langsung muncul di daftar tanpa refresh manual, dan bisa dicek
   masuk ke tabel `sidak` & `sidak_photos` lewat phpMyAdmin. File foto akan muncul
   secara fisik di folder `uploads/sidak/`.
2. **Edit data**: klik tombol Edit pada salah satu kartu → ubah beberapa field,
   hapus salah satu foto lama (klik ✕ pada preview), tambah foto baru → Simpan.
   Foto yang dihapus akan hilang dari `sidak_photos` dan filenya terhapus dari
   folder `uploads/sidak/`; foto yang tidak dihapus tetap ada.
3. **Hapus data**: klik tombol Hapus → konfirmasi. Baris di tabel `sidak` beserta
   seluruh baris terkait di `sidak_photos` akan otomatis terhapus (foreign key
   `ON DELETE CASCADE`), dan file fisik fotonya ikut dibersihkan.
4. **Pencarian & filter**: coba ketik di kotak pencarian, klik chip status
   (Open/Proses/Selesai), dan gunakan filter tanggal/bulan — semuanya tetap
   berjalan di sisi browser seperti versi lama, hanya sumber datanya (`entries`)
   sekarang berasal dari `api/sidak_list.php`.
5. **Export Excel**: klik tombol "Excel" di toolbar. File `.xlsx` akan terbuat
   dengan layout, kolom, tanda tangan, dan foto dokumentasi yang identik dengan
   versi lama — foto diambil ulang dari `uploads/sidak/` dan dikonversi ke base64
   hanya sementara di memori browser saat proses export (tidak disimpan ke DB).
6. **Pengaturan**: buka menu titik tiga di kanan atas, ubah salah satu field
   (mis. Petugas Sidak) → otomatis tersimpan ke tabel `settings` (ada jeda ~0.4
   detik agar tidak mengirim request di setiap ketikan).

## Migrasi Data Lama (opsional, sekali saja)

Jika Anda sebelumnya sudah punya data di localStorage (versi HTML lama):

1. Buka file HTML **lama** di browser yang sama tempat data lama tersimpan.
2. Buka DevTools (F12) → tab **Console**.
3. Copy-paste seluruh isi `migrate_export_console.js` ke console, tekan Enter.
   File `sidak_migration_export.json` akan otomatis terdownload.
4. Buka `http://localhost/laporan_sidak/migrate.php`, upload file JSON tadi,
   klik **Mulai Migrasi**.
5. Setelah selesai, cek dulu data di halaman utama (`index.php`) untuk
   memastikan semua data & foto sudah benar sebelum menghapus data lama di
   localStorage browser lama.

## Catatan Teknis

- Semua query database menggunakan **PDO prepared statements** (aman dari SQL
  injection).
- Foto divalidasi: hanya ekstensi `jpg/jpeg/png/webp`, dicek MIME type asli,
  dibatasi ukuran maksimum, dan disimpan dengan **nama file acak** (mencegah
  tebak nama file / overwrite).
- Path foto yang dihapus dibersihkan menggunakan `basename()` untuk mencegah
  directory traversal.
- Satu-satunya perubahan teks pada tampilan asli adalah kalimat di **footer**
  (sebelumnya menyebut "localStorage", sekarang menyebut "database server"),
  karena kalimat lama tidak lagi akurat setelah migrasi ke MySQL. Tidak ada
  perubahan warna, layout, ukuran, atau elemen visual lainnya.
