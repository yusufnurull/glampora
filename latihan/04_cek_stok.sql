-- ============================================================
-- LATIHAN 4 — CEK STOK LEWAT phpMyAdmin (Backend 2, Minggu 1)
-- Buka phpMyAdmin -> database glamping -> tab SQL -> jalankan satu per satu.
-- Ganti tanggal sesuai kebutuhan.
-- ============================================================

-- 1. Booking Forest Cabin (id 4) yang beririsan dengan rentang 19-21 Desember 2026
--    Rumus bentrok: booking.check_in < diminta.check_out AND booking.check_out > diminta.check_in
SELECT kode, check_in, check_out, jumlah_unit, status
FROM bookings
WHERE room_type_id = 4
  AND check_in  < '2026-12-21'
  AND check_out > '2026-12-19'
  AND status IN ('CONFIRMED', 'CHECKED_IN', 'PENDING_PAYMENT');

-- 2. Total unit terpakai pada SATU malam tertentu (malam 19 Desember)
SELECT COALESCE(SUM(jumlah_unit), 0) AS terpakai_malam_19
FROM bookings
WHERE room_type_id = 4
  AND check_in  <= '2026-12-19'
  AND check_out >  '2026-12-19'
  AND status IN ('CONFIRMED', 'CHECKED_IN', 'PENDING_PAYMENT');

-- 3. Sisa unit malam itu = total stok - terpakai
SELECT r.nama, r.total_stok,
       r.total_stok - COALESCE(SUM(b.jumlah_unit), 0) AS sisa_unit
FROM room_types r
LEFT JOIN bookings b
       ON b.room_type_id = r.id
      AND b.check_in  <= '2026-12-19'
      AND b.check_out >  '2026-12-19'
      AND b.status IN ('CONFIRMED', 'CHECKED_IN', 'PENDING_PAYMENT')
WHERE r.id = 4
GROUP BY r.id;

-- Catatan: untuk rentang beberapa malam, sisa unit = total stok - pemakaian TERBANYAK
-- di antara malam-malam itu. Itulah yang dihitung fungsi hitung_sisa_unit() di Minggu 2.

-- 4. Booking PENDING_PAYMENT yang sudah lewat 30 menit (nantinya dibatalkan otomatis)
SELECT kode, created_at, TIMESTAMPDIFF(MINUTE, created_at, NOW()) AS menit_berlalu
FROM bookings
WHERE status = 'PENDING_PAYMENT'
  AND created_at < NOW() - INTERVAL 30 MINUTE;