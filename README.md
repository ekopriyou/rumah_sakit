PANDUAN MENJALANKAN APLIKASI RUMAH SAKIT
==========================================

Aplikasi tahap awal ini dibuat dengan PHP native, MySQL/MariaDB, HTML, dan CSS untuk XAMPP.

Fitur yang tersedia
-------------------
1. Login petugas rumah sakit pusat dan tiga cabang.
2. Pendaftaran pasien baru.
3. Pencatatan rekam medis.
4. Cabang hanya melihat rekam medis yang dibuat di cabangnya.
5. Rumah sakit pusat melihat rekam medis dari seluruh cabang.
6. Pasien yang terdaftar di satu cabang dapat dibuatkan kunjungan di cabang lain menggunakan nomor rekam medis.

Struktur folder
---------------
D:\xampp\htdocs\rumah_sakit\
  assets\style.css
  config.php
  database.sql
  index.php
  login.php
  logout.php
  patient.php
  patient_form.php
  patients.php
  record_form.php
  doctors.php
  README.md

Cara menjalankan dengan XAMPP
-----------------------------
1. Pastikan XAMPP terpasang. Karena folder proyek menggunakan D:, lokasi XAMPP yang diasumsikan adalah D:\xampp.
2. Buka XAMPP Control Panel.
3. Klik Start pada Apache.
4. Klik Start pada MySQL.
5. Buka browser dan masuk ke:
   http://localhost/phpmyadmin
6. Pilih menu Import.
7. Pilih file:
   D:\xampp\htdocs\rumah_sakit\database.sql
8. Klik Go/Kirim dan tunggu sampai proses selesai.
9. Buka aplikasi:
   http://localhost/rumah_sakit/login.php

Jika XAMPP terpasang di C:\xampp
---------------------------------
Pindahkan folder rumah_sakit ke:
C:\xampp\htdocs\rumah_sakit

Kemudian gunakan URL yang sama:
http://localhost/rumah_sakit/login.php

Akun demo
---------
Username   Password   Akses
pusat      password   Melihat semua cabang
cabang1    password   Data Cabang 1
cabang2    password   Data Cabang 2
cabang3    password   Data Cabang 3

Alur penggunaan
---------------
1. Login dengan akun cabang.
2. Klik Pasien.
3. Daftarkan pasien baru pada cabang tersebut atau cari pasien dengan nomor RM.
4. Buka pasien.
5. Klik Catat kunjungan untuk menyimpan keluhan, diagnosis, tindakan, resep, dan catatan.
6. Login sebagai pusat untuk memeriksa data lintas cabang.
7. Admin membuka menu Referensi Dokter untuk menambah atau menonaktifkan dokter.
8. Saat mencatat kunjungan, pilih dokter dari dropdown Dokter.

Mencatat kunjungan pasien dari cabang lain
-------------------------------------------
1. Catat nomor RM pasien dari kartu/identitas pasien.
2. Buka URL berikut dan ganti ANGKA dengan ID pasien:
   http://localhost/rumah_sakit/patient.php?id=ANGKA
3. Klik Catat kunjungan.
4. Rekam medis baru akan tercatat pada cabang petugas yang sedang login.
5. Cabang tersebut hanya menampilkan rekam medis milik cabangnya; akun pusat dapat melihat semuanya.

Konfigurasi database
--------------------
File config.php menggunakan:
- Host: 127.0.0.1
- Database: rumah_sakit
- User: root
- Password: kosong

Jika MySQL Anda memakai password root, edit DB_PASS di config.php.

Pemeriksaan jika terjadi error
------------------------------
- Apache belum Start: pastikan port 80 tidak dipakai aplikasi lain.
- MySQL belum Start: pastikan port 3306 tidak dipakai aplikasi lain.
- Unknown database: import database.sql melalui phpMyAdmin.
- Database connection failed: periksa DB_HOST, DB_NAME, DB_USER, dan DB_PASS di config.php.
- Halaman tidak ditemukan: pastikan folder berada tepat di htdocs/rumah_sakit.

Catatan keamanan penting
------------------------
Aplikasi ini tetap prototipe dan belum boleh dipakai langsung untuk data pasien sungguhan atau dibuka ke internet. Perubahan ini menambah prepared statements pada alur yang disentuh, CSRF, pembatasan percobaan login berbasis sesi, regenerasi ID sesi saat login, cookie sesi HttpOnly/SameSite, header keamanan dasar, validasi input, serta pencatatan aktivitas admin ke log PHP. Perlindungan tersebut bukan jaminan bebas peretasan.

Sebelum produksi, wajib: ganti seluruh password demo dengan password unik kuat; simpan kredensial database di environment, bukan kode; gunakan akun MySQL khusus dengan hak minimum (jangan root); aktifkan HTTPS dan cookie Secure; terapkan pembatasan login lintas sesi/IP di server; lakukan audit akses dan uji keamanan profesional; backup terenkripsi dan uji pemulihan; konfigurasi server agar database.sql, backup, log, .git dan file konfigurasi tidak bisa diunduh; pembatasan akses berbasis cabang untuk semua halaman/API; kebijakan retensi/audit rekam medis serta kepatuhan regulasi data kesehatan. Batasi XAMPP lokal; jangan expose port Apache/MySQL ke internet.

Manajemen dokter
----------------
Dokter yang berhenti bekerja sebaiknya diubah menjadi Nonaktif, bukan dihapus. Dokter nonaktif tidak tersedia untuk kunjungan baru, tetapi data dokter dan riwayat lama tetap tersimpan. Edit memperbarui identitas referensi dan tidak menghapus rekam medis; aplikasi juga menyimpan snapshot nama dokter pada setiap catatan. Hindari hard-delete entitas klinis demi integritas riwayat dan audit.

Pembaruan skema dokter
----------------------
Saat aplikasi dibuka, config.php menambahkan tabel/kolom relasi bila belum tersedia. Pastikan backup database terlebih dahulu, lalu muat halaman sebagai pengujian. Jika migrasi gagal, periksa error log PHP/MySQL. Perubahan skema ini tidak menghapus isi rekam medis.

Akun demo
---------
Akun contoh di database.sql memakai password `password` dan diketahui umum. Hapus/ganti akun demo sebelum dipakai bersama atau dipublikasikan.

Verifikasi PHP dari Command Prompt
----------------------------------
Jika perintah php tidak dikenali, gunakan executable XAMPP secara langsung:
D:\xampp\php\php.exe -v
D:\xampp\php\php.exe -l D:\xampp\htdocs\rumah_sakit\login.php

Versi ini menggunakan password demo. Segera ganti password akun setelah instalasi.

Catatan desain database
-----------------------
- branches: empat lokasi rumah sakit.
- users: akun petugas dan cabang asalnya.
- patients: identitas pasien dan cabang pendaftaran awal.
- medical_records: catatan kunjungan, cabang tempat layanan diberikan, dan petugas pembuatnya.
- medical_records.branch_id adalah dasar pembatasan tampilan rekam medis cabang.
- Akun pusat dikenali dari branches.code = PUSAT dan mendapat akses seluruh cabang.

Untuk pengembangan berikutnya
-----------------------------
Modul yang dapat ditambahkan: dokter, poli, jadwal, rawat inap, IGD, farmasi, laboratorium, billing, BPJS, audit log, backup, dan API.

