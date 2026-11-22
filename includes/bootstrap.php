<?php
/**
 * BOOTSTRAP — Penanggung jawab: BACKEND 1
 *
 * Di-include di baris pertama SETIAP halaman dan endpoint API:
 *   require_once __DIR__ . '/includes/bootstrap.php';       // dari root
 *   require_once __DIR__ . '/../includes/bootstrap.php';    // dari api/ atau dashboard/
 *
 * Isinya: koneksi database, session, dan fungsi-fungsi bantu.
 */

$fileKoneksi = __DIR__ . '/../config/koneksi.php';
if (!file_exists($fileKoneksi)) {
    http_response_code(500);
    exit('File config/koneksi.php belum ada. Salin config/koneksi.example.php menjadi config/koneksi.php lalu sesuaikan.');
}
require_once $fileKoneksi;

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,                      // cookie tidak bisa dibaca JavaScript
        'samesite' => 'Lax',                     // proteksi dasar CSRF
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/audit.php';

segarkan_sesi($conn);
