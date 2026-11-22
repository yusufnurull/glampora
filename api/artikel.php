<?php
/**
 * API ARTIKEL — Penanggung jawab: BACKEND 1
 *
 * GET    api/artikel.php            -> artikel terbit (publik)
 * GET    api/artikel.php?semua=1    -> semua artikel termasuk draf (staff/admin)
 * GET    api/artikel.php?slug=...   -> detail berdasarkan slug
 * GET    api/artikel.php?id=1       -> detail berdasarkan id
 * POST   api/artikel.php            -> tambah (form-data, boleh berisi file `cover`)
 * POST   api/artikel.php?id=1       -> ubah
 * DELETE api/artikel.php?id=1       -> hapus
 *
 * Field: judul, ringkasan, isi, is_published, hapus_cover
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$method = api_mulai(['GET', 'POST', 'DELETE']);

function format_artikel(array $a): array
{
    return [
        'id'           => (int) $a['id'],
        'judul'        => $a['judul'],
        'slug'         => $a['slug'],
        'ringkasan'    => $a['ringkasan'],
        'isi'          => $a['isi'],
        'cover'        => $a['cover'],
        'cover_url'    => url_upload($a['cover'], 'assets/img/artikel.svg'),
        'is_published' => (bool) $a['is_published'],
        'penulis'      => $a['penulis'],
        'created_at'   => $a['created_at'],
        'tanggal'      => tanggal_id($a['created_at']),
    ];
}

$sql_dasar = 'SELECT a.*, u.nama AS penulis FROM articles a LEFT JOIN users u ON u.id = a.author_id';
$id = query_int('id');

if ($method === 'GET') {
    $lihat_semua = !empty($_GET['semua']) && is_staff(user_login());
    $filter_terbit = $lihat_semua ? '' : ' AND a.is_published = 1';

    if (!empty($_GET['slug'])) {
        $a = db_satu($conn, $sql_dasar . ' WHERE a.slug = ?' . $filter_terbit, 's', [$_GET['slug']]);
        $a ? json_ok(format_artikel($a)) : json_error('Artikel tidak ditemukan.', 404);
    }
    if ($id) {
        $a = db_satu($conn, $sql_dasar . ' WHERE a.id = ?' . $filter_terbit, 'i', [$id]);
        $a ? json_ok(format_artikel($a)) : json_error('Artikel tidak ditemukan.', 404);
    }
    json_ok(array_map('format_artikel', db_semua($conn, $sql_dasar . ' WHERE 1 = 1' . $filter_terbit . ' ORDER BY a.created_at DESC')));
}

$user = api_butuh_role(['ADMIN', 'STAFF']);

if ($method === 'DELETE') {
    $a = db_satu($conn, 'SELECT * FROM articles WHERE id = ?', 'i', [$id]) ?? json_error('Artikel tidak ditemukan.', 404);
    db_jalankan($conn, 'DELETE FROM articles WHERE id = ?', 'i', [$id]);
    hapus_gambar($a['cover']);
    catat_aksi($conn, $user['id'], 'ARTIKEL_HAPUS', 'articles', $id, ['judul' => $a['judul']]);
    json_ok(['id' => $id]);
}

// ---------------- POST: tambah / ubah ----------------
$lama = $id ? (db_satu($conn, 'SELECT * FROM articles WHERE id = ?', 'i', [$id]) ?? json_error('Artikel tidak ditemukan.', 404)) : null;

$judul     = input_teks('judul');
$ringkasan = input_teks('ringkasan');
$isi       = input_teks('isi');
$terbit    = array_key_exists('is_published', input()) ? (int) input_bool('is_published') : 1;

$errors = [];
if (mb_strlen($judul) < 5 || mb_strlen($judul) > 200)          $errors['judul'] = 'Judul 5–200 karakter.';
if (mb_strlen($ringkasan) < 10 || mb_strlen($ringkasan) > 300) $errors['ringkasan'] = 'Ringkasan 10–300 karakter.';
if (mb_strlen($isi) < 50)                                      $errors['isi'] = 'Isi artikel minimal 50 karakter.';
if ($errors) json_error('Periksa kembali data artikel.', 422, $errors);

// Slug unik: tambahkan angka jika sudah dipakai artikel lain
$slug_dasar = slugify($judul);
$slug = $slug_dasar;
for ($n = 2; db_satu($conn, 'SELECT id FROM articles WHERE slug = ? AND id <> ?', 'si', [$slug, $id]); $n++) {
    $slug = $slug_dasar . '-' . $n;
}

$cover = $lama['cover'] ?? null;
if (!empty($_FILES['cover']) && $_FILES['cover']['error'] !== UPLOAD_ERR_NO_FILE) {
    try {
        $cover_baru = simpan_gambar($_FILES['cover']);
    } catch (InvalidArgumentException $e) {
        json_error($e->getMessage(), 422, ['cover' => $e->getMessage()]);
    }
    hapus_gambar($cover);
    $cover = $cover_baru;
} elseif (input_bool('hapus_cover')) {
    hapus_gambar($cover);
    $cover = null;
}

if ($lama) {
    db_jalankan($conn,
        'UPDATE articles SET judul = ?, slug = ?, ringkasan = ?, isi = ?, cover = ?, is_published = ? WHERE id = ?',
        'sssssii', [$judul, $slug, $ringkasan, $isi, $cover, $terbit, $id]);
    catat_aksi($conn, $user['id'], 'ARTIKEL_UBAH', 'articles', $id, ['judul' => $judul]);
    $kode = 200;
} else {
    db_jalankan($conn,
        'INSERT INTO articles (judul, slug, ringkasan, isi, cover, is_published, author_id) VALUES (?, ?, ?, ?, ?, ?, ?)',
        'sssssii', [$judul, $slug, $ringkasan, $isi, $cover, $terbit, $user['id']]);
    $id = mysqli_insert_id($conn);
    catat_aksi($conn, $user['id'], 'ARTIKEL_TAMBAH', 'articles', $id, ['judul' => $judul]);
    $kode = 201;
}

json_ok(format_artikel(db_satu($conn, $sql_dasar . ' WHERE a.id = ?', 'i', [$id])), $kode);
