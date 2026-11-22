<?php
/**
 * LATIHAN 1 — PREPARED STATEMENT (Backend 2, Minggu 1)
 *
 * Tujuan: memahami kenapa query TIDAK boleh ditempel langsung dengan input user,
 * dan cara menggantinya dengan mysqli_prepare (dipakai di semua API Backend 2).
 *
 * Jalankan: http://localhost/glamping/latihan/01_prepared_statement.php?id=1
 * Coba juga: ?id=1 OR 1=1
 */
require_once __DIR__ . '/../config/koneksi.php';
header('Content-Type: text/plain; charset=utf-8');

$id = $_GET['id'] ?? ($argv[1] ?? '1');

// ------------------------------------------------------------------
// CARA LAMA (seperti di modul, BERBAHAYA): input ditempel ke query
// ------------------------------------------------------------------
$sql_bahaya = "SELECT id, nama FROM room_types WHERE id = $id";
echo "1) Query yang akan dikirim dengan cara lama:\n   $sql_bahaya\n";
echo "   Jika id diisi \"1 OR 1=1\", query mengambil SEMUA baris. Itulah SQL injection.\n";
echo "   (Query ini sengaja tidak dijalankan.)\n\n";

// ------------------------------------------------------------------
// CARA AMAN: prepared statement
// ------------------------------------------------------------------
// Langkah 1: siapkan query dengan tanda tanya (?) sebagai tempat nilai
$stmt = mysqli_prepare($conn, 'SELECT id, nama, total_stok, tarif_per_malam FROM room_types WHERE id = ?');

// Langkah 2: ikat nilainya. 'i' = integer, 's' = string, 'd' = desimal
$id_angka = (int) $id;   // "1 OR 1=1" menjadi 1
mysqli_stmt_bind_param($stmt, 'i', $id_angka);

// Langkah 3: jalankan dan ambil hasilnya
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
$kamar = mysqli_fetch_assoc($hasil);

echo "2) Hasil dengan prepared statement (id = $id_angka):\n";
echo $kamar
  ? "   {$kamar['nama']} | stok {$kamar['total_stok']} | Rp" . number_format($kamar['tarif_per_malam'], 0, ',', '.') . "/malam\n\n"
  : "   Kamar tidak ditemukan.\n\n";

// ------------------------------------------------------------------
// Contoh dengan beberapa parameter (pola yang dipakai di hitung_sisa_unit)
// ------------------------------------------------------------------
$room_type_id = 1;
$check_in = date('Y-m-d');
$check_out = date('Y-m-d', strtotime('+3 day'));

$stmt = mysqli_prepare(
  $conn,
  "SELECT kode, check_in, check_out, jumlah_unit, status FROM bookings
     WHERE room_type_id = ? AND check_in < ? AND check_out > ?"
);
mysqli_stmt_bind_param($stmt, 'iss', $room_type_id, $check_out, $check_in);   // urutan sesuai tanda ?
mysqli_stmt_execute($stmt);
$daftar = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

echo "3) Booking kamar #$room_type_id yang beririsan dengan $check_in s/d $check_out: " . count($daftar) . " booking\n";
foreach ($daftar as $b) {
  echo "   {$b['kode']} | {$b['check_in']} -> {$b['check_out']} | {$b['jumlah_unit']} unit | {$b['status']}\n";
}

echo "\nKesimpulan: di Minggu 2 dst, semua query memakai fungsi db_satu()/db_semua()/db_jalankan()\n";
echo "di includes/helpers.php (buatan Backend 1), yang isinya persis langkah 1-3 di atas.\n";