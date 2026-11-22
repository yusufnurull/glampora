-- ============================================================
-- Database Glampora — Aplikasi Manajemen Reservasi Glamping
-- Penanggung jawab: BACKEND 1
--
-- Cara pakai (phpMyAdmin):
--   1. Buat database baru bernama `glampora` (collation utf8mb4_general_ci)
--   2. Pilih database tersebut -> tab Import -> pilih file ini -> Go
--   3. Jalankan database/seed.php sekali untuk membuat akun demo & data contoh
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_logs, cancellations, payments, bookings, articles, room_types, categories, users;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- Akun (admin, staff, user)
-- ------------------------------------------------------------
CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nama          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  telepon       VARCHAR(20)  NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('ADMIN','STAFF','USER') NOT NULL DEFAULT 'USER',
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Master data kamar
-- ------------------------------------------------------------
CREATE TABLE categories (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nama       VARCHAR(100) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE room_types (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  category_id     INT NOT NULL,
  nama            VARCHAR(100) NOT NULL,
  deskripsi       TEXT NOT NULL,
  kapasitas       INT NOT NULL,              -- tamu maksimal per unit
  total_stok      INT NOT NULL,              -- jumlah unit yang dimiliki
  tarif_per_malam INT NOT NULL,              -- Rupiah
  fasilitas       VARCHAR(500) NULL,         -- dipisah koma
  foto            VARCHAR(255) NULL,         -- nama file di assets/uploads
  is_active       TINYINT(1) NOT NULL DEFAULT 1,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_room_category FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Reservasi
-- ------------------------------------------------------------
CREATE TABLE bookings (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  kode            VARCHAR(20) NOT NULL UNIQUE,     -- mis. GLP-7X2K9Q
  user_id         INT NULL,                        -- NULL jika memesan tanpa login
  room_type_id    INT NOT NULL,
  check_in        DATE NOT NULL,
  check_out       DATE NOT NULL,                   -- eksklusif: 10 -> 12 = 2 malam
  jumlah_unit     INT NOT NULL,
  nama_tamu       VARCHAR(100) NOT NULL,
  email_tamu      VARCHAR(150) NOT NULL,
  telepon_tamu    VARCHAR(20)  NOT NULL,
  dengan_asuransi TINYINT(1) NOT NULL DEFAULT 0,
  biaya_asuransi  INT NOT NULL DEFAULT 0,
  subtotal        INT NOT NULL,                    -- tarif x malam x unit
  total           INT NOT NULL,                    -- subtotal + asuransi
  status          ENUM('PENDING_PAYMENT','CONFIRMED','CHECKED_IN','COMPLETED','CANCELLED')
                  NOT NULL DEFAULT 'PENDING_PAYMENT',
  checked_in_at   DATETIME NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_booking_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_booking_room FOREIGN KEY (room_type_id) REFERENCES room_types(id),
  INDEX idx_booking_stok (room_type_id, check_in, check_out, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payments (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL UNIQUE,
  jumlah     INT NOT NULL,
  metode     VARCHAR(30) NOT NULL DEFAULT 'SIMULASI',
  status     ENUM('PENDING','PAID','FAILED','REFUNDED') NOT NULL DEFAULT 'PENDING',
  referensi  VARCHAR(50) NULL,
  paid_at    DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_payment_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cancellations (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  booking_id    INT NOT NULL UNIQUE,
  user_id       INT NULL,                    -- yang mengajukan
  alasan        TEXT NOT NULL,
  status        ENUM('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  persen_refund INT NULL,                    -- 100 / 50 / 0
  jumlah_refund INT NULL,
  catatan_admin TEXT NULL,                   -- catatan administrasi refund/deposit
  diproses_oleh INT NULL,                    -- staff yang memproses
  diproses_at   DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cancel_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  CONSTRAINT fk_cancel_user    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_cancel_staff   FOREIGN KEY (diproses_oleh) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Konten
-- ------------------------------------------------------------
CREATE TABLE articles (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  judul        VARCHAR(200) NOT NULL,
  slug         VARCHAR(220) NOT NULL UNIQUE,
  ringkasan    VARCHAR(300) NOT NULL,
  isi          TEXT NOT NULL,
  cover        VARCHAR(255) NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  author_id    INT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_article_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Catatan tindakan staff/admin
-- ------------------------------------------------------------
CREATE TABLE audit_logs (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NULL,
  aksi       VARCHAR(50) NOT NULL,      -- mis. KAMAR_UBAH, PEMBATALAN_TERIMA
  entitas    VARCHAR(50) NOT NULL,      -- mis. room_types, bookings
  entitas_id INT NULL,
  detail     TEXT NULL,                 -- JSON
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_audit (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
