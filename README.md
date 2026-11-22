# Glampora — Aplikasi Reservasi Glamping

Proyek UAS Pemrograman Web. Pengunjung bisa melihat kamar, memesan, membayar (simulasi), check-in, dan mengajukan pembatalan. Staff dan admin mengelola kamar, artikel, pembatalan, akun, serta melihat statistik.

**Stack:** PHP native + MySQL (XAMPP/phpMyAdmin) + Bootstrap + jQuery AJAX.

## Tim

| Peran | Nama | NIM | Kode branch | Tanggung jawab |
|---|---|---|---|---|
| Backend 1 | Yusuf Nurul Huda | K3524038 | `be1` | Auth, akun, master data (kamar, kategori, artikel), audit log, statistik, deploy |
| Backend 2 | Muhammad Rafli Rafsanjani | K3524030 | `be2` | Ketersediaan kamar, booking, pembayaran dummy, check-in, pembatalan & refund |
| Frontend 1 | Atikah Salsabila | K3524008 | `fe1` | Halaman pengunjung: landing, login/register, reservasi, pembayaran, check-in, artikel, profil |
| Frontend 2 | Nadhira Zulsyifa S | K3524082 | `fe2` | Dashboard staff & admin, komponen bersama, grafik |

## Setup lokal (XAMPP)

1. Clone repo ke folder `htdocs` XAMPP:
   ```bash
   cd C:\xampp\htdocs        # sesuaikan lokasi XAMPP-mu
   git clone https://github.com/yusufnurull/glampora.git
   cd glampora
   git checkout develop
   ```
2. Nyalakan **Apache** dan **MySQL** di XAMPP Control Panel.
3. Buka `http://localhost/phpmyadmin` → **New** → nama database `glampora`, collation `utf8mb4_general_ci` → Create.
4. Pilih database `glampora` → tab **Import** → pilih `database/glampora.sql` → **Go**. Cek tab **Designer**: harus ada 8 tabel beserta relasinya.
5. Salin `config/koneksi.example.php` menjadi `config/koneksi.php`. Sesuaikan `BASE_URL` dengan nama folder di htdocs (mis. `'/glampora'`). File ini **tidak** ikut di-commit.
6. Buat akun demo & data contoh: buka `http://localhost/glampora/database/seed.php` **sekali**.
   Untuk mengulang dari awal: impor ulang `glampora.sql`, lalu buka `seed.php` lagi.
7. Buka `http://localhost/glampora/`.

Butuh **PHP 8.0 atau lebih baru** (cek di XAMPP Control Panel → Shell → `php -v`).

## Akun demo

| Role | Email | Password |
|---|---|---|
| Admin | admin@demo.test | Admin#2026 |
| Staff | staff@demo.test | Staff#2026 |
| User | user@demo.test | User#2026 |

## Struktur folder

```
glampora/
├── config/       koneksi.example.php (di-commit), koneksi.php (lokal, tidak di-commit)
├── includes/     bootstrap, helpers, response, auth, audit (BE1) · booking, refund (BE2) · header, footer (FE2)
├── api/          auth, kategori, kamar, artikel, profil, akun, audit, statistik (BE1)
│                 availability, bookings, payments, checkin, cancellations (BE2)
├── dashboard/    halaman staff & admin (FE2)
├── assets/       css, js, img, uploads
├── database/     glampora.sql, seed.php
├── docs/         api.md, erd.md, proses-bisnis.md, test-case.md
├── tests/        uji aturan bisnis (BE2)
└── *.php         halaman pengunjung (FE1)
```

`config/`, `includes/`, `tests/`, file `.sql`, dan file PHP di `assets/uploads/` diblokir dari browser lewat `.htaccess`.

## Aturan kerja

- **Format response API** (semua endpoint):
  - Berhasil: `{"success": true, "data": ...}`
  - Gagal: `{"success": false, "message": "Pesan untuk user", "errors": {"field": "pesan"}}` dengan status HTTP 400/401/403/404/409/422
- **Penamaan:** tabel & kolom `snake_case`; nilai status HURUF_BESAR (`PENDING_PAYMENT`, `CONFIRMED`, `CHECKED_IN`, `COMPLETED`, `CANCELLED`).
- **Keamanan:** semua query yang memakai variabel wajib prepared statement (pakai `db_semua`, `db_satu`, `db_jalankan` di `includes/helpers.php`). Output ke HTML wajib lewat `e()`.
- **Kontrak API dulu:** tulis/ubah `docs/api.md` sebelum backend & frontend mengerjakan fitur yang sama.
- **Postman:** request selain GET wajib header `X-Requested-With: XMLHttpRequest` (jQuery mengirimnya otomatis).

## Aturan Git

### Branch

| Branch | Fungsi |
|---|---|
| `main` | Versi stabil yang di-deploy. Hanya menerima merge dari `develop` saat checkpoint/rilis. |
| `develop` | Gabungan kerja semua anggota. Semua PR diarahkan ke sini. |
| `feature/<peran>-<fitur>` | Kerja harian. Contoh: `feature/be1-auth`, `feature/be2-booking`, `feature/fe1-landing`, `feature/fe2-dashboard` |
| `fix/<peran>-<masalah>` | Perbaikan bug. Contoh: `fix/be1-slug-artikel` |

Alur: `git checkout develop` → `git pull` → `git checkout -b feature/be1-xxx` → commit → `git push -u origin feature/be1-xxx` → buat **Pull Request ke `develop`** → minimal **1 reviewer** menyetujui → merge.

### Penamaan commit

```
<tipe>(<lingkup>): <ringkasan singkat>
```

| Tipe | Dipakai untuk |
|---|---|
| `feat` | Fitur baru |
| `fix` | Perbaikan bug |
| `docs` | Dokumentasi saja (README, docs/) |
| `style` | Tampilan/CSS, atau rapikan format kode tanpa ubah logika |
| `refactor` | Ubah struktur kode tanpa ubah perilaku |
| `test` | Test case / skrip uji |
| `chore` | Konfigurasi, struktur folder, .gitignore, dsb. |

Lingkup yang dipakai: `auth`, `db`, `config`, `core`, `kamar`, `kategori`, `artikel`, `akun`, `profil`, `audit`, `statistik`, `booking`, `pembayaran`, `checkin`, `pembatalan`, `ui`, `dashboard`, `landing`, `deploy`.

Contoh:
```
feat(auth): tambah API register dan login
fix(kamar): foto lama tidak terhapus saat diganti
docs(api): kontrak endpoint artikel
chore: inisialisasi struktur folder dan .gitignore
```

Aturan singkat: bahasa Indonesia, huruf kecil di awal ringkasan, maksimal ±70 karakter, satu commit = satu perubahan yang utuh, jangan pernah commit `config/koneksi.php`.
