<?php
/**
 * API AUTENTIKASI — Penanggung jawab: BACKEND 1
 *
 * GET  api/auth.php                 -> data user yang sedang login (atau null)
 * POST api/auth.php?aksi=register   {nama, email, telepon?, password}
 * POST api/auth.php?aksi=login      {email, password, next?}
 * POST api/auth.php?aksi=logout     (keluar lewat AJAX; versi halaman: logout.php)
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$method = api_mulai(['GET', 'POST']);

if ($method === 'GET') {
    json_ok(user_login());
}

$aksi = $_GET['aksi'] ?? '';

if ($aksi === 'register') {
    $nama     = input_teks('nama');
    $email    = strtolower(input_teks('email'));
    $telepon  = input_teks('telepon');
    $password = (string) (input()['password'] ?? '');

    $errors = [];
    if (mb_strlen($nama) < 2 || mb_strlen($nama) > 100) $errors['nama'] = 'Nama 2–100 karakter.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))    $errors['email'] = 'Format email tidak valid.';
    if ($telepon !== '' && !telepon_valid($telepon))    $errors['telepon'] = 'Nomor HP tidak valid (contoh: 081234567890).';
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Password minimal 8 karakter dan mengandung huruf serta angka.';
    }
    if ($errors) json_error('Periksa kembali data yang diisi.', 422, $errors);

    if (db_satu($conn, 'SELECT id FROM users WHERE email = ?', 's', [$email])) {
        json_error('Email sudah terdaftar.', 409, ['email' => 'Email sudah terdaftar.']);
    }

    db_jalankan($conn,
        "INSERT INTO users (nama, email, telepon, password_hash, role) VALUES (?, ?, ?, ?, 'USER')",
        'ssss', [$nama, $email, $telepon ?: null, password_hash($password, PASSWORD_DEFAULT)]);

    $user = db_satu($conn, 'SELECT * FROM users WHERE id = ?', 'i', [mysqli_insert_id($conn)]);
    simpan_sesi($user);
    json_ok(['user' => user_login(), 'redirect' => url('profil.php')], 201);
}

if ($aksi === 'login') {
    // Batasi percobaan login: 5 kali gagal -> tunggu 5 menit
    $gagal = $_SESSION['login_gagal'] ?? ['jumlah' => 0, 'sampai' => 0];
    if ($gagal['sampai'] > time()) {
        json_error('Terlalu banyak percobaan. Coba lagi dalam ' . ceil(($gagal['sampai'] - time()) / 60) . ' menit.', 429);
    }

    $email    = strtolower(input_teks('email'));
    $password = (string) (input()['password'] ?? '');
    if ($email === '' || $password === '') json_error('Email dan password wajib diisi.', 422);

    $user = db_satu($conn, 'SELECT * FROM users WHERE email = ?', 's', [$email]);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        $gagal['jumlah']++;
        if ($gagal['jumlah'] >= 5) $gagal = ['jumlah' => 0, 'sampai' => time() + 300];
        $_SESSION['login_gagal'] = $gagal;
        json_error('Email atau password salah.', 401);
    }
    if (!$user['is_active']) json_error('Akun Anda dinonaktifkan. Hubungi admin.', 403);

    unset($_SESSION['login_gagal']);
    simpan_sesi($user);

    $default = in_array($user['role'], ['ADMIN', 'STAFF'], true) ? url('dashboard/index.php') : url('profil.php');
    json_ok(['user' => user_login(), 'redirect' => redirect_aman(input_teks('next'), $default)]);
}

if ($aksi === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    json_ok(['redirect' => url('index.php')]);
}

json_error('Aksi tidak dikenal.', 404);
