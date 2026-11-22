# Kontrak API Glampora

Semua endpoint ada di folder `api/` dan dipanggil langsung, mis. `http://localhost/glampora/api/kamar.php?id=1`.

## Aturan umum

- **Format berhasil:** `{"success": true, "data": ...}`
- **Format gagal:** `{"success": false, "message": "Pesan untuk user", "errors": {"nama_field": "pesan"}}` (`errors` hanya ada untuk kesalahan isian)
- **Kode status:** 200 OK · 201 dibuat · 401 belum login · 403 tidak berhak / header kurang · 404 tidak ditemukan · 405 method salah · 409 bentrok (duplikat, masih dipakai) · 422 isian tidak valid · 429 terlalu banyak percobaan · 500 error server
- **Header wajib untuk selain GET:** `X-Requested-With: XMLHttpRequest` (jQuery otomatis; di Postman tambahkan manual)
- **Body:** JSON (`Content-Type: application/json`) atau form biasa. Endpoint yang menerima file (kamar, artikel) memakai `multipart/form-data` dengan method **POST** (PHP tidak bisa membaca file pada PUT).
- **Login memakai session cookie.** Di Postman, login dulu di tab yang sama agar cookie tersimpan.

---

## Backend 1

### Auth — `api/auth.php`

| Method | URL | Body | Akses |
|---|---|---|---|
| GET | `auth.php` | – | publik → data user login atau `null` |
| POST | `auth.php?aksi=register` | `nama`, `email`, `telepon?`, `password` | publik |
| POST | `auth.php?aksi=login` | `email`, `password`, `next?` | publik |
| POST | `auth.php?aksi=logout` | – | publik |

Validasi register: nama 2–100 karakter, email valid & belum terdaftar (409), telepon `08…`/`+628…` (opsional), password ≥ 8 karakter berisi huruf dan angka. Setelah register user langsung login.

Login: gagal 5× → 429 selama 5 menit. Akun nonaktif → 403. `redirect`: dashboard untuk ADMIN/STAFF, `profil.php` untuk USER, atau `next` jika aman (harus diawali `/`).

```json
// POST auth.php?aksi=login  {"email":"staff@demo.test","password":"Staff#2026"}
{"success": true, "data": {
  "user": {"id": 2, "nama": "Staff Demo", "email": "staff@demo.test", "telepon": "081200000002", "role": "STAFF"},
  "redirect": "/glampora/dashboard/index.php"}}
```

Halaman `logout.php` juga tersedia untuk tombol/link logout biasa (redirect ke `index.php`).

### Kategori — `api/kategori.php`

| Method | URL | Body | Akses |
|---|---|---|---|
| GET | `kategori.php` | – | publik |
| POST | `kategori.php` | `nama` | ADMIN, STAFF |
| PUT | `kategori.php?id=1` | `nama` | ADMIN, STAFF |
| DELETE | `kategori.php?id=1` | – | ADMIN, STAFF (409 jika masih dipakai kamar) |

```json
// GET kategori.php
{"success": true, "data": [{"id": 3, "nama": "Cabin", "jumlah_kamar": 1}, {"id": 2, "nama": "Dome", "jumlah_kamar": 1}]}
```

### Jenis kamar — `api/kamar.php`

| Method | URL | Body | Akses |
|---|---|---|---|
| GET | `kamar.php` | – | publik (hanya kamar aktif, urut tarif termurah) |
| GET | `kamar.php?semua=1` | – | ADMIN, STAFF (termasuk nonaktif) |
| GET | `kamar.php?id=1` | – | publik (404 jika nonaktif, kecuali staff + `semua=1`) |
| POST | `kamar.php` | form-data (lihat field) | ADMIN, STAFF → 201 |
| POST | `kamar.php?id=1` | form-data | ADMIN, STAFF → ubah |
| DELETE | `kamar.php?id=1` | – | ADMIN, STAFF → dihapus, atau **dinonaktifkan** jika pernah dipesan |

Field: `category_id`, `nama` (3–100), `deskripsi` (≥ 10), `kapasitas` (1–20), `total_stok` (0–500), `tarif_per_malam` (≥ 1000), `fasilitas` (teks dipisah koma, ≤ 500), `is_active` (default 1), `foto` (file JPG/PNG/WEBP ≤ 2 MB, opsional), `hapus_foto` (1 untuk menghapus foto).

```json
// GET kamar.php?id=2
{"success": true, "data": {
  "id": 2, "category_id": 1, "kategori": "Tenda Safari", "nama": "Family Safari Tent",
  "deskripsi": "Tenda keluarga dengan dua kasur ...", "kapasitas": 4, "total_stok": 3, "tarif_per_malam": 1250000,
  "fasilitas": ["2 kasur", "Kamar mandi dalam", "Api unggun", "Sarapan"],
  "fasilitas_teks": "2 kasur, Kamar mandi dalam, Api unggun, Sarapan",
  "foto": null, "foto_url": "/glampora/assets/img/placeholder.svg", "is_active": true}}

// DELETE kamar.php?id=1  (kamar sudah pernah dipesan)
{"success": true, "data": {"id": 1, "dinonaktifkan": true}}
```

### Artikel — `api/artikel.php`

| Method | URL | Body | Akses |
|---|---|---|---|
| GET | `artikel.php` | – | publik (hanya terbit, terbaru dulu) |
| GET | `artikel.php?semua=1` | – | ADMIN, STAFF (termasuk draf) |
| GET | `artikel.php?slug=tips-memilih-tipe-kamar-glamping-yang-tepat` | – | publik |
| GET | `artikel.php?id=1` | – | publik |
| POST | `artikel.php` | form-data | ADMIN, STAFF → 201 |
| POST | `artikel.php?id=1` | form-data | ADMIN, STAFF → ubah |
| DELETE | `artikel.php?id=1` | – | ADMIN, STAFF |

Field: `judul` (5–200), `ringkasan` (10–300), `isi` (≥ 50), `is_published` (default 1 — kirim selalu saat mengubah draf), `cover` (file, opsional), `hapus_cover`. Slug dibuat otomatis dari judul dan dibuat unik.

```json
// GET artikel.php?slug=checklist-barang-bawaan-saat-glamping
{"success": true, "data": {
  "id": 2, "judul": "Checklist Barang Bawaan Saat Glamping", "slug": "checklist-barang-bawaan-saat-glamping",
  "ringkasan": "Daftar barang wajib ...", "isi": "Sebelum berangkat, ...", "cover": null,
  "cover_url": "/glampora/assets/img/artikel.svg", "is_published": true, "penulis": "Staff Demo",
  "created_at": "2026-11-22 14:47:05", "tanggal": "22 Nov 2026"}}
```

### Profil — `api/profil.php` (wajib login)

| Method | URL | Body |
|---|---|---|
| GET | `profil.php` | – |
| PUT | `profil.php` | `nama`, `telepon` |
| POST | `profil.php?aksi=password` | `password_lama`, `password_baru` |

```json
// GET profil.php
{"success": true, "data": {"id": 3, "nama": "User Demo", "email": "user@demo.test", "telepon": "081200000003", "role": "USER", "created_at": "2026-11-22 14:47:05"}}
```

### Manajemen akun — `api/akun.php` (khusus ADMIN)

| Method | URL | Body |
|---|---|---|
| GET | `akun.php?role=STAFF&q=budi` | – (filter opsional) |
| POST | `akun.php` | `nama`, `email`, `telepon?`, `password`, `role` (ADMIN/STAFF/USER) |
| PUT | `akun.php?id=5` | `nama?`, `telepon?`, `role?`, `is_active?`, `password?` (hanya field yang dikirim yang diubah) |

Admin tidak boleh menurunkan role atau menonaktifkan akunnya sendiri (409). Perubahan role/nonaktif langsung berlaku ke sesi akun tersebut.

```json
// GET akun.php?role=STAFF
{"success": true, "data": [{"id": 2, "nama": "Staff Demo", "email": "staff@demo.test", "telepon": "081200000002",
  "role": "STAFF", "is_active": true, "created_at": "2026-11-22 14:47:04", "tanggal_daftar": "22 Nov 2026"}]}
```

### Audit log — `api/audit.php` (khusus ADMIN)

`GET audit.php?user_id=2&dari=2026-11-01&sampai=2026-11-30&halaman=1` (semua parameter opsional, 25 baris per halaman)

```json
{"success": true, "data": {
  "total": 15, "halaman": 1, "jumlah_halaman": 1,
  "staff": [{"id": 1, "nama": "Admin Demo", "role": "ADMIN"}, {"id": 2, "nama": "Staff Demo", "role": "STAFF"}],
  "log": [{"id": 15, "waktu": "22 Nov 2026, 14:48", "nama": "Admin Demo", "role": "ADMIN",
           "aksi": "AKUN_UBAH", "entitas": "users", "entitas_id": 8,
           "detail": {"email": "sari@glampora.test", "is_active": [true, false]}}]}}
```

Kode aksi: `KAMAR_TAMBAH`, `KAMAR_UBAH`, `KAMAR_HAPUS`, `KAMAR_NONAKTIF`, `KATEGORI_TAMBAH`, `KATEGORI_UBAH`, `KATEGORI_HAPUS`, `ARTIKEL_TAMBAH`, `ARTIKEL_UBAH`, `ARTIKEL_HAPUS`, `PEMBATALAN_TERIMA`, `PEMBATALAN_TOLAK`, `AKUN_TAMBAH`, `AKUN_UBAH`.

Cara mencatat dari endpoint lain (Backend 2):
```php
catat_aksi($conn, $user['id'], 'PEMBATALAN_TERIMA', 'cancellations', $id, ['kode' => $kode, 'refund' => $jumlah]);
```

### Statistik — `api/statistik.php` (ADMIN, STAFF)

`GET statistik.php?periode=bulanan|mingguan|harian` (default bulanan: 6 bulan; mingguan: 8 minggu; harian: 14 hari)

```json
{"success": true, "data": {
  "ringkasan": {"booking_hari_ini": 1, "tamu_menginap": 1, "pembatalan_menunggu": 1,
                "pendapatan_bulan_ini": 5690000, "pendapatan_total": 142020000},
  "grafik": {"periode": "bulanan",
             "label": ["Jun 2026", "Jul 2026", "Agu 2026", "Sep 2026", "Okt 2026", "Nov 2026"],
             "penyewaan": [0, 9, 3, 11, 7, 3], "pembatalan": [0, 1, 1, 1, 4, 2]},
  "okupansi": [{"kamar": "Bubble Dome", "malam_terpakai": 9, "kapasitas_malam": 120, "persen": 7.5}]}}
```

- Penyewaan dihitung dari tanggal check-in (status CONFIRMED, CHECKED_IN, COMPLETED); pembatalan dari tanggal diajukan.
- Pendapatan = pembayaran simulasi (PAID + REFUNDED) dikurangi refund yang disetujui.
- Okupansi = malam-unit terpakai ÷ (total stok × 30 malam), 30 hari terakhir.

---

## Backend 2

_(Diisi Backend 2: availability, bookings, payments, checkin, cancellations.)_
