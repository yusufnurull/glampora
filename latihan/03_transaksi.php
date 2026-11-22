<?php
/**
 * LATIHAN 3 — TRANSAKSI (Backend 2, Minggu 1)
 *
 * Tujuan: memahami begin / commit / rollback sebelum dipakai di api/bookings.php.
 * Latihan ini AMAN: semua perubahan di-rollback, database tidak berubah.
 *
 * Jalankan: http://localhost/glamping/latihan/03_transaksi.php
 * Syarat : database glamping sudah diimpor dan seed.php sudah dijalankan.
 */
require_once __DIR__ . '/../config/koneksi.php';
header('Content-Type: text/plain; charset=utf-8');

function hitung_booking(mysqli $conn): int
{
  return (int) mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS n FROM bookings'))['n'];
}

echo "Jumlah booking awal       : " . hitung_booking($conn) . "\n";

// ------------------------------------------------------------------
// 1. Mulai transaksi: perubahan belum permanen sampai commit
// ------------------------------------------------------------------
mysqli_begin_transaction($conn);

try {
  // 2. Kunci baris kamar. Di aplikasi asli, ini membuat pemesan lain untuk kamar yang sama
  //    MENUNGGU sampai transaksi ini selesai, sehingga unit terakhir tidak terjual dua kali.
  $stmt = mysqli_prepare($conn, 'SELECT id, nama, total_stok FROM room_types WHERE id = ? FOR UPDATE');
  $room_id = 1;
  mysqli_stmt_bind_param($stmt, 'i', $room_id);
  mysqli_stmt_execute($stmt);
  $kamar = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
  echo "Kamar dikunci             : {$kamar['nama']} (stok {$kamar['total_stok']})\n";

  // 3. Simpan booking percobaan
  $stmt = mysqli_prepare(
    $conn,
    "INSERT INTO bookings (kode, room_type_id, check_in, check_out, jumlah_unit, nama_tamu, email_tamu, telepon_tamu, subtotal, total)
         VALUES (?, ?, ?, ?, 1, 'Latihan', 'latihan@test.id', '081200000000', 850000, 850000)"
  );
  $kode = 'LAT-' . random_int(1000, 9999);
  $ci = date('Y-m-d', strtotime('+60 day'));
  $co = date('Y-m-d', strtotime('+61 day'));
  mysqli_stmt_bind_param($stmt, 'siss', $kode, $room_id, $ci, $co);
  mysqli_stmt_execute($stmt);
  echo "Setelah INSERT ($kode)  : " . hitung_booking($conn) . "  <- terlihat di koneksi ini, belum permanen\n";

  // 4. Simulasikan kegagalan, misalnya stok ternyata habis
  throw new RuntimeException('Simulasi: stok habis, batalkan semua langkah.');

  // mysqli_commit($conn);   // <- di aplikasi asli: dipanggil jika semua langkah berhasil
} catch (Throwable $e) {
  // 5. Rollback: semua perubahan sejak begin_transaction dibatalkan
  mysqli_rollback($conn);
  echo "Error                     : {$e->getMessage()}\n";
}

echo "Setelah ROLLBACK          : " . hitung_booking($conn) . "  <- kembali seperti awal\n\n";

echo "Pola ini persis yang dipakai di api/bookings.php (Minggu 3):\n";
echo "  begin -> kunci kamar (FOR UPDATE) -> hitung sisa unit -> INSERT booking -> commit\n";
echo "  dan rollback jika ada langkah yang gagal.\n";
echo "Catatan: transaksi hanya bekerja pada tabel ENGINE=InnoDB (sudah diatur di glamping.sql).\n";