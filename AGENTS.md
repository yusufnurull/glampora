# AGENTS.md — Aturan proyek Glampora untuk asisten AI

Baca file ini sebelum mengubah kode. Ikuti aturan di sini meskipun ada cara lain yang "lebih modern".

## Proyek

Glampora: website reservasi glamping (proyek UAS Pemrograman Web, tim 4 orang).
Stack WAJIB: **PHP native (8.x) + MySQL/MariaDB (XAMPP) + Bootstrap + jQuery AJAX**.
Tidak boleh memakai framework (Laravel, CodeIgniter, dll.), Composer, npm, ORM, atau bundler.

Lingkungan pengembangan: Windows + XAMPP, terminal PowerShell di VS Code.
- Jalankan PHP milik XAMPP, bukan PHP lain di PATH: `<lokasi XAMPP>\php\php.exe` (mis. `C:\xampp\php\php.exe`). Tanyakan ke user jika lokasinya belum jelas.
- Di PowerShell pakai `curl.exe` (bukan `curl`, karena itu alias Invoke-WebRequest).
- Aplikasi dibuka di `http://localhost/<nama-folder>/`. `BASE_URL` diatur di `config/koneksi.php`.

## Struktur & pemilik file

```
config/      koneksi.example.php (commit) · koneksi.php (lokal, JANGAN commit)        → Backend 1
includes/    bootstrap, helpers, response, auth, audit                                 → Backend 1
             booking, refund                                                           → Backend 2
             header, footer                                                            → Frontend 2
api/         auth, kategori, kamar, artikel, profil, akun, audit, statistik           → Backend 1
             availability, bookings, payments, checkin, cancellations                  → Backend 2
dashboard/   halaman staff & admin                                                     → Frontend 2
*.php root   index, reservasi, pembayaran, checkin, artikel, artikel_detail,
             login, register, profil                                                   → Frontend 1
             logout.php                                                                → Backend 1
assets/      css, js, img, uploads (uploads tidak di-commit)
database/    glampora.sql, seed.php                                                    → Backend 1
docs/        api.md, erd.md, proses-bisnis.md, test-case.md
tests/       skrip uji aturan bisnis                                                   → Backend 2
```

Jangan mengubah file milik peran lain kecuali user memintanya secara eksplisit.

## Aturan kode

1. Baris pertama setiap halaman dan endpoint: `require_once __DIR__ . '/includes/bootstrap.php';` (dari root) atau `require_once __DIR__ . '/../includes/bootstrap.php';` (dari `api/`, `dashboard/`, `database/`). Bootstrap menyediakan `$conn`, session, dan semua helper.
2. **Database:** semua query yang memakai nilai variabel WAJIB prepared statement lewat helper `db_semua()`, `db_satu()`, `db_jalankan()`. Jangan pernah menempel variabel ke string SQL. Nama tabel/kolom `snake_case`.
3. **Endpoint API** (`api/*.php`) selalu diawali `$method = api_mulai([...method yang diizinkan...]);`, membaca input dengan `input()`, `input_teks()`, `input_int()`, `input_bool()`, `query_int()`, dan mengakhiri dengan `json_ok($data, $kode)` atau `json_error($pesan, $kode, $errors)`.
   - Format berhasil: `{"success": true, "data": ...}`
   - Format gagal: `{"success": false, "message": "...", "errors": {"field": "pesan"}}`
   - Kode: 401 belum login, 403 tidak berhak, 404 tidak ada, 409 bentrok/duplikat, 422 isian tidak valid.
4. **Hak akses:** halaman pakai `cek_login()` / `cek_role(['ADMIN','STAFF'])`; API pakai `api_butuh_login()` / `api_butuh_role([...])`. Role: `ADMIN`, `STAFF`, `USER`.
5. **Audit log:** setiap aksi staff/admin yang mengubah data memanggil `catat_aksi($conn, $user['id'], 'KODE_AKSI', 'nama_tabel', $id, [detail])`. Kode aksi HURUF_BESAR, mis. `KAMAR_UBAH`.
6. **Nilai status** HURUF_BESAR: `PENDING_PAYMENT`, `CONFIRMED`, `CHECKED_IN`, `COMPLETED`, `CANCELLED` (booking); `PENDING`, `APPROVED`, `REJECTED` (pembatalan); `PENDING`, `PAID`, `FAILED`, `REFUNDED` (pembayaran).
7. **Output HTML** dari data user/database selalu lewat `e()`. URL internal lewat `url('path.php')`.
8. **Upload gambar** hanya lewat `simpan_gambar()` / `hapus_gambar()`.
9. Nama variabel, fungsi, komentar, dan pesan error dalam **Bahasa Indonesia**, mengikuti gaya kode yang sudah ada.
10. Jangan menghapus atau melemahkan file `.htaccess` di `config/`, `includes/`, `tests/`, `database/`, `assets/uploads/`.
11. Sebelum fitur baru antara backend & frontend dikerjakan, kontraknya ditulis di `docs/api.md`.

## Aturan Git

- Branch: `main` (stabil/deploy) · `develop` (integrasi, tujuan semua PR) · `feature/<peran>-<fitur>` · `fix/<peran>-<masalah>`. Kode peran: `be1`, `be2`, `fe1`, `fe2`.
- Selalu buat branch fitur dari `develop` yang sudah di-`git pull`.
- Format commit: `<tipe>(<lingkup>): <ringkasan>` — tipe `feat|fix|docs|style|refactor|test|chore`, ringkasan bahasa Indonesia huruf kecil, maks ±70 karakter. Contoh: `feat(auth): tambah API register dan login`.
- Satu commit = satu perubahan utuh. Jangan `git add .` membabi buta: cek `git status` dulu.
- JANGAN commit: `config/koneksi.php`, isi `assets/uploads/`, `_panduan/`, `.vscode/`.
- Jangan `git push --force` ke `main`/`develop`, dan jangan merge ke `main` kecuali user memintanya.

## Cara mengecek hasil

- Cek sintaks: `<lokasi XAMPP>\php\php.exe -l <file.php>`
- Request selain GET ke API wajib header `X-Requested-With: XMLHttpRequest`. Di PowerShell kirim body sebagai form (bukan JSON, karena tanda kutip JSON mudah rusak) dan simpan cookie di folder TEMP, bukan di folder proyek:
  ```powershell
  $ck = "$env:TEMP\glampora-cookie.txt"
  curl.exe -s -c $ck -X POST -H "X-Requested-With: XMLHttpRequest" --data-urlencode "email=admin@demo.test" --data-urlencode "password=Admin#2026" "http://localhost/<nama-folder>/api/auth.php?aksi=login"
  curl.exe -s -b $ck "http://localhost/<nama-folder>/api/statistik.php?periode=bulanan"
  ```
- Akun demo: `admin@demo.test / Admin#2026`, `staff@demo.test / Staff#2026`, `user@demo.test / User#2026`.
