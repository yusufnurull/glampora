<?php
/**
 * API PROFIL USER — Penanggung jawab: BACKEND 1
 *
 * GET  api/profil.php                 -> data diri user login
 * PUT  api/profil.php                 {nama, telepon}
 * POST api/profil.php?aksi=password   {password_lama, password_baru}
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$method = api_mulai(['GET', 'PUT', 'POST']);
$user = api_butuh_login();

if ($method === 'GET') {
    json_ok(db_satu($conn, 'SELECT id, nama, email, telepon, role, created_at FROM users WHERE id = ?', 'i', [$user['id']]));
}

if ($method === 'PUT') {
    $nama = input_teks('nama');
    $telepon = input_teks('telepon');
    $errors = [];
    if (mb_strlen($nama) < 2 || mb_strlen($nama) > 100) $errors['nama'] = 'Nama 2–100 karakter.';
    if ($telepon !== '' && !telepon_valid($telepon)) $errors['telepon'] = 'Nomor HP tidak valid (contoh: 081234567890).';
    if ($errors) json_error('Periksa kembali data diri.', 422, $errors);

    db_jalankan($conn, 'UPDATE users SET nama = ?, telepon = ? WHERE id = ?', 'ssi', [$nama, $telepon ?: null, $user['id']]);
    $_SESSION['user']['nama'] = $nama;
    $_SESSION['user']['telepon'] = $telepon ?: null;
    json_ok(user_login());
}

if (($_GET['aksi'] ?? '') === 'password') {
    $lama = (string) (input()['password_lama'] ?? '');
    $baru = (string) (input()['password_baru'] ?? '');
    $data = db_satu($conn, 'SELECT password_hash FROM users WHERE id = ?', 'i', [$user['id']]);

    if (!password_verify($lama, $data['password_hash'])) {
        json_error('Password lama salah.', 422, ['password_lama' => 'Password lama salah.']);
    }
    if (strlen($baru) < 8 || !preg_match('/[A-Za-z]/', $baru) || !preg_match('/[0-9]/', $baru)) {
        json_error('Password baru minimal 8 karakter dan mengandung huruf serta angka.', 422,
            ['password_baru' => 'Minimal 8 karakter, berisi huruf dan angka.']);
    }
    db_jalankan($conn, 'UPDATE users SET password_hash = ? WHERE id = ?', 'si', [password_hash($baru, PASSWORD_DEFAULT), $user['id']]);
    json_ok(['diubah' => true]);
}

json_error('Aksi tidak dikenal.', 404);
