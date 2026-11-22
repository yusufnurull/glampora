<?php
/**
 * PLACEHOLDER dari Backend 1 (Minggu 1) — untuk cek setup XAMPP tiap anggota.
 * Frontend 1 akan MENGGANTI isi file ini dengan landing page Glampora.
 */
require_once __DIR__ . '/config/koneksi.php';
$jumlah_tabel = (int) mysqli_fetch_row(mysqli_query($conn,
    'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'))[0];
?>
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><title>Glampora</title></head>
<body style="font-family: sans-serif; padding: 2rem">
    <h1>Glampora</h1>
    <p>Koneksi database OK. Jumlah tabel: <strong><?= $jumlah_tabel ?></strong> (harusnya 8).</p>
</body>
</html>
