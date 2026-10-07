# tugasForm

Form Registrasi Member Top-Up Game dengan UI Modern, Gen-Z, Minimalis, & Clean Look.

## 🚀 Fitur Utama
- **Front-End (`registrasi.html`)**:
  - UI Menggunakan **Tailwind CSS** bertema neon gaming dark mode.
  - Form input teks (`username`), password, dan upload file gambar foto profil.
  - Tombol submit form menggunakan **gambar ikon** (`icon-submit.svg`).
- **Back-End (`api/proses.php`)**:
  - Validasi tipe MIME & ekstensi gambar (JPG, PNG, WEBP) serta batas ukuran (maksimal 2MB).
  - Sanitasi nama file dan penyimpanan ke folder `uploads/`.
  - Enkripsi password menggunakan `password_hash()`.
  - Integrasi database MySQL (Prepared Statement).
  - Siap di-deploy ke **Vercel** via runtime `vercel-php`.

## ⚙️ Menjalankan di Lokal (XAMPP)
1. Pindahkan folder ini ke `C:\xampp\htdocs\tugasForm`.
2. Buka **XAMPP Control Panel**, lalu klik **Start** pada modul **Apache** dan **MySQL**.
3. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`), buat database `db_topup_game` dan tabel `users` (dapat melihat query CRUD di `database.sql`).
4. Akses form di browser: `http://localhost/tugasForm/registrasi.html`.

## ☁️ Menjalankan di Vercel
1. Hubungkan repository GitHub ini ke Vercel.
2. Vercel akan membaca konfigurasi `vercel.json` dan menjalankan backend PHP melalui `vercel-php`.
3. (Opsional) Jika ingin menghubungkan ke database online saat live di Vercel (misal TiDB Serverless, Aiven, atau Railway MySQL), tambahkan Environment Variables di dashboard Vercel:
   - `DB_HOST`
   - `DB_USER`
   - `DB_PASS`
   - `DB_NAME`
   - `DB_PORT`
