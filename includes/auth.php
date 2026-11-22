<?php
/**
 * AUTENTIKASI & PROTEKSI RUTE — Penanggung jawab: BACKEND 1
 *
 * Untuk HALAMAN:
 *   $user = cek_login();                   // wajib login (semua role)
 *   $user = cek_role(['ADMIN', 'STAFF']);  // wajib role tertentu
 *
 * Untuk API:
 *   $user = api_butuh_login();
 *   $user = api_butuh_role(['ADMIN']);
 */

/** Data user yang sedang login: ['id','nama','email','telepon','role'] atau null. */
function user_login(): ?array
{
    return $_SESSION['user'] ?? null;
}

function simpan_sesi(array $user): void
{
    session_regenerate_id(true);   // cegah session fixation
    $_SESSION['user'] = [
        'id'      => (int) $user['id'],
        'nama'    => $user['nama'],
        'email'   => $user['email'],
        'telepon' => $user['telepon'],
        'role'    => $user['role'],
    ];
}

/**
 * Dipanggil setiap request (dari bootstrap.php): jika admin mengubah role atau
 * menonaktifkan akun, perubahan langsung berlaku tanpa user harus login ulang.
 */
function segarkan_sesi(mysqli $conn): void
{
    $sesi = user_login();
    if (!$sesi) return;

    $user = db_satu($conn, 'SELECT id, nama, email, telepon, role, is_active FROM users WHERE id = ?', 'i', [$sesi['id']]);
    if (!$user || !$user['is_active']) {
        $_SESSION = [];
        return;
    }
    $_SESSION['user'] = array_merge($sesi, [
        'nama'    => $user['nama'],
        'email'   => $user['email'],
        'telepon' => $user['telepon'],
        'role'    => $user['role'],
    ]);
}

function cek_login(): array
{
    $user = user_login();
    if (!$user) {
        header('Location: ' . url('login.php?next=' . urlencode($_SERVER['REQUEST_URI'])));
        exit;
    }
    return $user;
}

function cek_role(array $role_diizinkan): array
{
    $user = cek_login();
    if (!in_array($user['role'], $role_diizinkan, true)) {
        http_response_code(403);
        // 403.php dibuat Frontend 2. Selama belum ada, tampilkan pesan sederhana.
        $halaman_403 = __DIR__ . '/../403.php';
        if (is_file($halaman_403)) {
            require $halaman_403;
        } else {
            echo '403 — Anda tidak memiliki akses ke halaman ini.';
        }
        exit;
    }
    return $user;
}

function api_butuh_login(): array
{
    $user = user_login();
    if (!$user) json_error('Silakan login terlebih dahulu.', 401);
    return $user;
}

function api_butuh_role(array $role_diizinkan): array
{
    $user = api_butuh_login();
    if (!in_array($user['role'], $role_diizinkan, true)) {
        json_error('Anda tidak memiliki akses untuk tindakan ini.', 403);
    }
    return $user;
}

function is_staff(?array $user): bool
{
    return $user && in_array($user['role'], ['ADMIN', 'STAFF'], true);
}

/** Validasi URL tujuan setelah login agar tidak bisa diarahkan ke situs lain (open redirect). */
function redirect_aman(?string $tujuan, string $default): string
{
    if ($tujuan && str_starts_with($tujuan, '/') && !str_starts_with($tujuan, '//')) {
        return $tujuan;
    }
    return $default;
}
