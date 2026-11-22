<?php
/**
 * LATIHAN 2 — TANGGAL & RUMUS BENTROK (Backend 2, Minggu 1)
 *
 * Tujuan: memahami tiga hitungan yang menjadi dasar logika stok dan refund:
 *   a. jumlah malam menginap
 *   b. H-berapa sebelum check-in (untuk aturan refund asuransi)
 *   c. rumus dua rentang tanggal bentrok (overlap)
 *
 * Jalankan: http://localhost/glamping/latihan/02_tanggal.php   (tidak butuh database)
 */
header('Content-Type: text/plain; charset=utf-8');
date_default_timezone_set('Asia/Jakarta');

// ---------------- a. Jumlah malam ----------------
// Check-out bersifat eksklusif: check-in 10, check-out 12 = malam tanggal 10 dan 11 = 2 malam
function hitung_malam(string $check_in, string $check_out): int
{
  $awal = new DateTime($check_in);
  $akhir = new DateTime($check_out);
  return (int) $awal->diff($akhir)->days;
}

echo "a. Jumlah malam\n";
echo "   10 Des -> 12 Des = " . hitung_malam('2026-12-10', '2026-12-12') . " malam (harus 2)\n";
echo "   31 Des -> 2 Jan  = " . hitung_malam('2026-12-31', '2027-01-02') . " malam (harus 2, lintas tahun aman)\n\n";

// ---------------- b. H-berapa sebelum check-in ----------------
// format('%r%a'): %a = selisih hari, %r = tanda minus jika tanggal kedua lebih awal
function hari_sebelum(string $tanggal_pengajuan, string $check_in): int
{
  $pengajuan = new DateTime(substr($tanggal_pengajuan, 0, 10));   // jam diabaikan
  return (int) $pengajuan->diff(new DateTime($check_in))->format('%r%a');
}

echo "b. H-berapa (aturan asuransi: lebih dari 3 hari = refund 100%, selain itu 50%)\n";
foreach (['2026-12-06', '2026-12-07', '2026-12-09 23:30:00', '2026-12-11'] as $tgl) {
  $h = hari_sebelum($tgl, '2026-12-10');
  if ($h <= 0) {
    // Di aplikasi, pembatalan ditolak jika tanggal check-in sudah tiba (lihat api/cancellations.php)
    echo "   diajukan $tgl, check-in 2026-12-10 -> sudah lewat -> pembatalan ditolak\n";
    continue;
  }
  $refund = $h > 3 ? '100%' : '50%';
  echo "   diajukan $tgl, check-in 2026-12-10 -> H-$h -> refund $refund\n";
}
echo "\n";

// ---------------- c. Rumus bentrok ----------------
// Dua rentang A dan B bentrok jika:  A.check_in < B.check_out  DAN  A.check_out > B.check_in
function bentrok(string $a_in, string $a_out, string $b_in, string $b_out): bool
{
  return $a_in < $b_out && $a_out > $b_in;   // string YYYY-MM-DD bisa dibandingkan langsung
}

$kasus = [
  ['10', '12', '10', '12', 'tanggal sama persis'],
  ['10', '12', '11', '13', 'beririsan sebagian'],
  ['10', '15', '11', '12', 'B di dalam A'],
  ['10', '12', '12', '14', 'check-out A = check-in B (tamu A pulang, tamu B datang)'],
  ['10', '12', '13', '14', 'tidak bersentuhan'],
];
echo "c. Rumus bentrok (A = booking yang sudah ada, B = permintaan baru)\n";
foreach ($kasus as [$ai, $ao, $bi, $bo, $ket]) {
  $hasil = bentrok("2026-12-$ai", "2026-12-$ao", "2026-12-$bi", "2026-12-$bo") ? 'BENTROK' : 'aman';
  echo "   A $ai-$ao, B $bi-$bo: $hasil  ($ket)\n";
}

echo "\nRumus c dipakai di query hitung_sisa_unit():  WHERE check_in < ? AND check_out > ?\n";