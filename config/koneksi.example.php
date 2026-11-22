<?php
/**
 * KONFIGURASI KONEKSI — Penanggung jawab: BACKEND 1
 *
 * Salin file ini menjadi `config/koneksi.php`, lalu sesuaikan isinya.
 * `koneksi.php` TIDAK di-commit ke Git karena isinya berbeda di tiap laptop/hosting.
 */

// ---- Database (XAMPP default: user root tanpa password) ----
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'glampora';

// ---- Aplikasi ----
// Nama folder proyek di htdocs. Contoh: http://localhost/glampora -> '/glampora'
// Jika aplikasi ada di root domain hosting (https://namaweb.com), isi dengan ''.
define('BASE_URL', '/glampora');

// true saat development (pesan error lengkap), false di hosting/production
define('APP_DEBUG', true);

date_default_timezone_set('Asia/Jakarta');

// Error mysqli dilempar sebagai exception agar bisa ditangkap (try/catch)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    mysqli_set_charset($conn, 'utf8mb4');
    // Samakan zona waktu MySQL dengan PHP (WIB) agar NOW() dan date() cocok
    mysqli_query($conn, "SET time_zone = '+07:00'");
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit(APP_DEBUG ? 'Koneksi database gagal: ' . $e->getMessage() : 'Koneksi database gagal.');
}
