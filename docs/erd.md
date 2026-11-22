# ERD — Database Glampora

Sumber kebenaran skema: `database/glampora.sql`. Diagram di bawah ditampilkan otomatis oleh GitHub. Untuk versi draw.io: buka draw.io → **Arrange → Insert → Advanced → Mermaid**, tempel isi blok `mermaid` di bawah, lalu rapikan.

```mermaid
erDiagram
    users |o--o{ bookings : "memesan (user_id, boleh NULL)"
    users |o--o{ cancellations : "mengajukan (user_id)"
    users |o--o{ cancellations : "memproses (diproses_oleh)"
    users |o--o{ articles : "menulis (author_id)"
    users |o--o{ audit_logs : "melakukan (user_id)"
    categories ||--o{ room_types : "mengelompokkan"
    room_types ||--o{ bookings : "dipesan"
    bookings ||--o| payments : "dibayar"
    bookings ||--o| cancellations : "dibatalkan"

    users {
        INT id PK
        VARCHAR nama
        VARCHAR email UK
        VARCHAR telepon
        VARCHAR password_hash
        ENUM role "ADMIN | STAFF | USER"
        TINYINT is_active
        DATETIME created_at
        DATETIME updated_at
    }
    categories {
        INT id PK
        VARCHAR nama UK
        DATETIME created_at
    }
    room_types {
        INT id PK
        INT category_id FK
        VARCHAR nama
        TEXT deskripsi
        INT kapasitas "tamu per unit"
        INT total_stok "jumlah unit"
        INT tarif_per_malam "Rupiah"
        VARCHAR fasilitas "dipisah koma"
        VARCHAR foto
        TINYINT is_active
        DATETIME created_at
        DATETIME updated_at
    }
    bookings {
        INT id PK
        VARCHAR kode UK "GLP-XXXXXX"
        INT user_id FK
        INT room_type_id FK
        DATE check_in
        DATE check_out "eksklusif"
        INT jumlah_unit
        VARCHAR nama_tamu
        VARCHAR email_tamu
        VARCHAR telepon_tamu
        TINYINT dengan_asuransi
        INT biaya_asuransi
        INT subtotal
        INT total
        ENUM status "PENDING_PAYMENT | CONFIRMED | CHECKED_IN | COMPLETED | CANCELLED"
        DATETIME checked_in_at
        DATETIME created_at
        DATETIME updated_at
    }
    payments {
        INT id PK
        INT booking_id FK,UK
        INT jumlah
        VARCHAR metode "SIMULASI"
        ENUM status "PENDING | PAID | FAILED | REFUNDED"
        VARCHAR referensi
        DATETIME paid_at
        DATETIME created_at
    }
    cancellations {
        INT id PK
        INT booking_id FK,UK
        INT user_id FK
        TEXT alasan
        ENUM status "PENDING | APPROVED | REJECTED"
        INT persen_refund "100 / 50 / 0"
        INT jumlah_refund
        TEXT catatan_admin
        INT diproses_oleh FK
        DATETIME diproses_at
        DATETIME created_at
    }
    articles {
        INT id PK
        VARCHAR judul
        VARCHAR slug UK
        VARCHAR ringkasan
        TEXT isi
        VARCHAR cover
        TINYINT is_published
        INT author_id FK
        DATETIME created_at
        DATETIME updated_at
    }
    audit_logs {
        INT id PK
        INT user_id FK
        VARCHAR aksi "mis. KAMAR_UBAH"
        VARCHAR entitas "mis. room_types"
        INT entitas_id
        TEXT detail "JSON"
        DATETIME created_at
    }
```

## Penjelasan tabel

| Tabel | Isi | Catatan desain |
|---|---|---|
| `users` | Semua akun: admin, staff, user | Password disimpan sebagai hash (`password_hash`). Akun tidak dihapus, cukup `is_active = 0`. |
| `categories` | Kategori kamar (Tenda Safari, Dome, Cabin) | Nama unik. Tidak bisa dihapus selama masih dipakai kamar. |
| `room_types` | Jenis kamar beserta stok dan tarif | Stok = jumlah unit fisik; sisa per tanggal dihitung dari `bookings`. Kamar yang pernah dipesan hanya dinonaktifkan agar riwayat tetap utuh. |
| `bookings` | Reservasi | `check_out` eksklusif (10→12 = 2 malam). `user_id` boleh NULL untuk tamu tanpa login. Indeks `idx_booking_stok` mempercepat cek ketersediaan. |
| `payments` | Pembayaran simulasi, 1 per booking | `ON DELETE CASCADE` ikut terhapus bersama booking. |
| `cancellations` | Pengajuan pembatalan, 1 per booking | Menyimpan persen & jumlah refund serta staff yang memproses. |
| `articles` | Artikel/blog | `slug` unik dibuat dari judul (duplikat diberi akhiran `-2`, `-3`, ...). |
| `audit_logs` | Catatan setiap aksi staff/admin | `detail` berisi JSON (mis. nilai lama → baru). Diisi lewat `catat_aksi()`. |

Semua tabel memakai `ENGINE=InnoDB` (wajib untuk foreign key dan transaksi di fitur booking) dan `utf8mb4`.
