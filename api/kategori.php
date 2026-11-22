<?php
/**
 * API KATEGORI KAMAR — Penanggung jawab: BACKEND 1
 *
 * GET    api/kategori.php          -> semua kategori (+ jumlah kamar)
 * POST   api/kategori.php          {nama}   (staff/admin)
 * PUT    api/kategori.php?id=1     {nama}   (staff/admin)
 * DELETE api/kategori.php?id=1              (staff/admin, hanya jika tidak dipakai kamar)
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$method = api_mulai(['GET', 'POST', 'PUT', 'DELETE']);

if ($method === 'GET') {
    json_ok(db_semua($conn,
        'SELECT c.id, c.nama, COUNT(r.id) AS jumlah_kamar
         FROM categories c LEFT JOIN room_types r ON r.category_id = c.id
         GROUP BY c.id ORDER BY c.nama'));
}

$user = api_butuh_role(['ADMIN', 'STAFF']);
$id = query_int('id');

if ($method === 'DELETE') {
    $kat = db_satu($conn, 'SELECT * FROM categories WHERE id = ?', 'i', [$id]) ?? json_error('Kategori tidak ditemukan.', 404);
    $dipakai = db_satu($conn, 'SELECT COUNT(*) AS n FROM room_types WHERE category_id = ?', 'i', [$id])['n'];
    if ($dipakai > 0) json_error('Kategori masih dipakai oleh ' . $dipakai . ' jenis kamar.', 409);

    db_jalankan($conn, 'DELETE FROM categories WHERE id = ?', 'i', [$id]);
    catat_aksi($conn, $user['id'], 'KATEGORI_HAPUS', 'categories', $id, ['nama' => $kat['nama']]);
    json_ok(['id' => $id]);
}

// POST & PUT
$nama = input_teks('nama');
if (mb_strlen($nama) < 2 || mb_strlen($nama) > 100) json_error('Nama kategori 2–100 karakter.', 422, ['nama' => 'Nama kategori 2–100 karakter.']);

$duplikat = db_satu($conn, 'SELECT id FROM categories WHERE nama = ? AND id <> ?', 'si', [$nama, $id]);
if ($duplikat) json_error('Nama kategori sudah ada.', 409, ['nama' => 'Nama kategori sudah ada.']);

if ($method === 'POST') {
    db_jalankan($conn, 'INSERT INTO categories (nama) VALUES (?)', 's', [$nama]);
    $id = mysqli_insert_id($conn);
    catat_aksi($conn, $user['id'], 'KATEGORI_TAMBAH', 'categories', $id, ['nama' => $nama]);
    json_ok(['id' => $id, 'nama' => $nama], 201);
}

// PUT
if (!db_satu($conn, 'SELECT id FROM categories WHERE id = ?', 'i', [$id])) json_error('Kategori tidak ditemukan.', 404);
db_jalankan($conn, 'UPDATE categories SET nama = ? WHERE id = ?', 'si', [$nama, $id]);
catat_aksi($conn, $user['id'], 'KATEGORI_UBAH', 'categories', $id, ['nama' => $nama]);
json_ok(['id' => $id, 'nama' => $nama]);
