# Brangkas PA Asril

Aplikasi PHP–MySQL untuk Laragon dengan landing page, admin menggunakan **private key**, dan enkripsi AES-256-GCM sebelum konten disimpan ke database.

## Instalasi

1. Gunakan PHP 7.3+ pada Laragon (AES-GCM membutuhkan OpenSSL modern), lalu salin folder ke `C:\laragon\www\brangkas-pa-asril`.
2. Jalankan Apache dan MySQL pada Laragon.
3. Impor `database/brangkas_asril.sql` di phpMyAdmin.
4. Bila perlu ubah kredensial MySQL di `config.php`.
5. Akses `http://localhost/brangkas-pa-asril/install.php`, buat private key minimal 12 karakter, lalu buka aplikasi.

Private key tidak disimpan. Kunci AES dihasilkan dari PBKDF2-SHA256 (210.000 iterasi dan salt acak). Database hanya menyimpan ciphertext, IV, serta authentication tag.
