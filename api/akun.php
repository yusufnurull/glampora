<?php
/**
 * API MANAJEMEN AKUN — Penanggung jawab: BACKEND 1 (khusus ADMIN)
 *
 * GET  api/akun.php?role=STAFF&q=budi   -> daftar akun (filter opsional)
 * POST api/akun.php                     {nama, email, telepon?, password, role}
 * PUT  api/akun.php?id=5                {nama?, telepon?, role?, is_active?, password?}
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$method = api_mulai(['GET', 'POST', 'PUT']);
$admin = api_butuh_role(['ADMIN']);
$ROLE = ['ADMIN', 'STAFF', 'USER'];

function format_akun(array $u): array
{
    return [
        'id' => (int) $u['id'], 'nama' => $u['nama'], 'email' => $u['email'], 'telepon' => $u['telepon'],
        'role' => $u['role'], 'is_active' => (bool) $u['is_active'],
        'created_at' => $u['created_at'], 'tanggal_daftar' => tanggal_id($u['created_at']),
    ];
}

if ($method === 'GET') {
    $sql = 'SELECT * FROM users WHERE 1 = 1';
    $tipe = '';
    $param = [];
    if (in_array($_GET['role'] ?? '', $ROLE, true)) {
        $sql .= ' AND role = ?';
        $tipe .= 's';
        $param[] = $_GET['role'];
    }
    if (!empty($_GET['q'])) {
        $sql .= ' AND (nama LIKE ? OR email LIKE ?)';
        $tipe .= 'ss';
        $cari = '%' . $_GET['q'] . '%';
        array_push($param, $cari, $cari);
    }
    $sql .= " ORDER BY FIELD(role, 'ADMIN', 'STAFF', 'USER'), nama";
    json_ok(array_map('format_akun', db_semua($conn, $sql, $tipe, $param)));
}

if ($method === 'POST') {
    $nama     = input_teks('nama');
    $email    = strtolower(input_teks('email'));
    $telepon  = input_teks('telepon');
    $password = (string) (input()['password'] ?? '');
    $role     = input_teks('role');

    $errors = [];
    if (mb_strlen($nama) < 2) $errors['nama'] = 'Nama minimal 2 karakter.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Format email tidak valid.';
    elseif (db_satu($conn, 'SELECT id FROM users WHERE email = ?', 's', [$email])) $errors['email'] = 'Email sudah terdaftar.';
    if ($telepon !== '' && !telepon_valid($telepon)) $errors['telepon'] = 'Nomor HP tidak valid.';
    if (strlen($password) < 8) $errors['password'] = 'Password minimal 8 karakter.';
    if (!in_array($role, $ROLE, true)) $errors['role'] = 'Role tidak valid.';
    if ($errors) json_error('Periksa kembali data akun.', 422, $errors);

    db_jalankan($conn,
        'INSERT INTO users (nama, email, telepon, password_hash, role) VALUES (?, ?, ?, ?, ?)',
        'sssss', [$nama, $email, $telepon ?: null, password_hash($password, PASSWORD_DEFAULT), $role]);
    $id = mysqli_insert_id($conn);
    catat_aksi($conn, $admin['id'], 'AKUN_TAMBAH', 'users', $id, ['email' => $email, 'role' => $role]);
    json_ok(format_akun(db_satu($conn, 'SELECT * FROM users WHERE id = ?', 'i', [$id])), 201);
}

// ---------------- PUT ----------------
$id = query_int('id');
$akun = db_satu($conn, 'SELECT * FROM users WHERE id = ?', 'i', [$id]) ?? json_error('Akun tidak ditemukan.', 404);
$in = input();

$nama    = array_key_exists('nama', $in) ? input_teks('nama') : $akun['nama'];
$telepon = array_key_exists('telepon', $in) ? input_teks('telepon') : (string) $akun['telepon'];
$role    = array_key_exists('role', $in) ? input_teks('role') : $akun['role'];
$aktif   = array_key_exists('is_active', $in) ? (int) input_bool('is_active') : (int) $akun['is_active'];

$errors = [];
if (mb_strlen($nama) < 2) $errors['nama'] = 'Nama minimal 2 karakter.';
if ($telepon !== '' && !telepon_valid($telepon)) $errors['telepon'] = 'Nomor HP tidak valid.';
if (!in_array($role, $ROLE, true)) $errors['role'] = 'Role tidak valid.';
if ($errors) json_error('Periksa kembali data akun.', 422, $errors);

if ($id === $admin['id'] && ($role !== 'ADMIN' || !$aktif)) {
    json_error('Anda tidak dapat menurunkan role atau menonaktifkan akun sendiri.', 409);
}

db_jalankan($conn, 'UPDATE users SET nama = ?, telepon = ?, role = ?, is_active = ? WHERE id = ?',
    'sssii', [$nama, $telepon ?: null, $role, $aktif, $id]);

if (!empty($in['password'])) {
    if (strlen($in['password']) < 8) json_error('Password minimal 8 karakter.', 422, ['password' => 'Password minimal 8 karakter.']);
    db_jalankan($conn, 'UPDATE users SET password_hash = ? WHERE id = ?', 'si', [password_hash($in['password'], PASSWORD_DEFAULT), $id]);
}

$perubahan = [];
if ($role !== $akun['role']) $perubahan['role'] = [$akun['role'], $role];
if ($aktif !== (int) $akun['is_active']) $perubahan['is_active'] = [(bool) $akun['is_active'], (bool) $aktif];
if (!empty($in['password'])) $perubahan['password'] = 'direset';
catat_aksi($conn, $admin['id'], 'AKUN_UBAH', 'users', $id, ['email' => $akun['email']] + $perubahan);

json_ok(format_akun(db_satu($conn, 'SELECT * FROM users WHERE id = ?', 'i', [$id])));
