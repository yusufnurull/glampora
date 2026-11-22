<?php
/**
 * FORMAT RESPONSE API — Penanggung jawab: BACKEND 1
 *
 * Semua endpoint di folder api/ memakai format yang sama:
 *   Berhasil : {"success": true,  "data": ...}
 *   Gagal    : {"success": false, "message": "...", "errors": {...}}
 */

function json_ok($data = null, int $kode = 200): void
{
    http_response_code($kode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $pesan, int $kode = 400, array $errors = []): void
{
    http_response_code($kode);
    header('Content-Type: application/json; charset=utf-8');
    $body = ['success' => false, 'message' => $pesan];
    if ($errors) $body['errors'] = $errors;
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Panggil di awal setiap endpoint API.
 * - Membatasi method yang diizinkan
 * - Menolak request pengubah data yang tidak berasal dari AJAX halaman ini
 *   (header X-Requested-With otomatis dikirim jQuery) sebagai proteksi dasar CSRF
 * - Mengubah error tak terduga menjadi JSON 500
 *
 * Mengembalikan method request (GET/POST/PUT/DELETE).
 * Catatan Postman: tambahkan header X-Requested-With: XMLHttpRequest
 */
function api_mulai(array $method_diizinkan): string
{
    set_exception_handler(function (Throwable $e) {
        error_log((string) $e);
        json_error(APP_DEBUG ? 'Error server: ' . $e->getMessage() : 'Terjadi kesalahan pada server.', 500);
    });

    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, $method_diizinkan, true)) {
        json_error('Method tidak diizinkan.', 405);
    }
    if ($method !== 'GET' && ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
        json_error('Permintaan ditolak (header X-Requested-With tidak ada).', 403);
    }
    return $method;
}

/** Ambil body request: JSON (application/json) atau form biasa ($_POST). */
function input(): array
{
    static $data = null;
    if ($data !== null) return $data;

    $tipe = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($tipe, 'application/json') !== false) {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
    } else {
        $data = $_POST;
    }
    return $data;
}

/** Ambil satu field dari body sebagai string yang sudah di-trim. */
function input_teks(string $nama, string $default = ''): string
{
    $nilai = input()[$nama] ?? $default;
    return is_scalar($nilai) ? trim((string) $nilai) : $default;
}

function input_int(string $nama, int $default = 0): int
{
    $nilai = filter_var(input()[$nama] ?? null, FILTER_VALIDATE_INT);
    return $nilai === false || $nilai === null ? $default : $nilai;
}

function input_bool(string $nama): bool
{
    return filter_var(input()[$nama] ?? false, FILTER_VALIDATE_BOOLEAN);
}

/** Ambil parameter query string (?id=5) sebagai int, 0 jika tidak ada/tidak valid. */
function query_int(string $nama): int
{
    return (int) filter_var($_GET[$nama] ?? 0, FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);
}
