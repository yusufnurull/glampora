<?php
/**
 * API JENIS KAMAR — Penanggung jawab: BACKEND 1
 *
 * GET    api/kamar.php            -> kamar aktif (publik)
 * GET    api/kamar.php?semua=1    -> semua kamar termasuk nonaktif (staff/admin)
 * GET    api/kamar.php?id=1       -> detail satu kamar
 * POST   api/kamar.php            -> tambah (form-data, boleh berisi file `foto`)
 * POST   api/kamar.php?id=1       -> ubah   (form-data; PHP tidak bisa membaca file pada PUT)
 * DELETE api/kamar.php?id=1       -> hapus, atau dinonaktifkan jika sudah pernah dipesan
 *
 * Field: category_id, nama, deskripsi, kapasitas, total_stok, tarif_per_malam, fasilitas, is_active, hapus_foto
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$method = api_mulai(['GET', 'POST', 'DELETE']);

function format_kamar(array $k): array
{
    return [
        'id'              => (int) $k['id'],
        'category_id'     => (int) $k['category_id'],
        'kategori'        => $k['kategori'],
        'nama'            => $k['nama'],
        'deskripsi'       => $k['deskripsi'],
        'kapasitas'       => (int) $k['kapasitas'],
        'total_stok'      => (int) $k['total_stok'],
        'tarif_per_malam' => (int) $k['tarif_per_malam'],
        'fasilitas'       => array_values(array_filter(array_map('trim', explode(',', (string) $k['fasilitas'])))),
        'fasilitas_teks'  => (string) $k['fasilitas'],
        'foto'            => $k['foto'],
        'foto_url'        => url_upload($k['foto']),
        'is_active'       => (bool) $k['is_active'],
    ];
}

$sql_dasar = 'SELECT r.*, c.nama AS kategori FROM room_types r JOIN categories c ON c.id = r.category_id';
$id = query_int('id');

if ($method === 'GET') {
    $lihat_semua = !empty($_GET['semua']) && is_staff(user_login());
    if ($id) {
        $kamar = db_satu($conn, $sql_dasar . ' WHERE r.id = ?' . ($lihat_semua ? '' : ' AND r.is_active = 1'), 'i', [$id]);
        $kamar ? json_ok(format_kamar($kamar)) : json_error('Kamar tidak ditemukan.', 404);
    }
    $daftar = db_semua($conn, $sql_dasar . ($lihat_semua ? '' : ' WHERE r.is_active = 1') . ' ORDER BY r.tarif_per_malam');
    json_ok(array_map('format_kamar', $daftar));
}

$user = api_butuh_role(['ADMIN', 'STAFF']);

if ($method === 'DELETE') {
    $kamar = db_satu($conn, 'SELECT * FROM room_types WHERE id = ?', 'i', [$id]) ?? json_error('Kamar tidak ditemukan.', 404);
    $pernah_dipesan = db_satu($conn, 'SELECT COUNT(*) AS n FROM bookings WHERE room_type_id = ?', 'i', [$id])['n'] > 0;

    if ($pernah_dipesan) {
        // Riwayat booking harus tetap utuh, jadi kamar hanya dinonaktifkan
        db_jalankan($conn, 'UPDATE room_types SET is_active = 0 WHERE id = ?', 'i', [$id]);
        catat_aksi($conn, $user['id'], 'KAMAR_NONAKTIF', 'room_types', $id, ['nama' => $kamar['nama']]);
        json_ok(['id' => $id, 'dinonaktifkan' => true]);
    }
    db_jalankan($conn, 'DELETE FROM room_types WHERE id = ?', 'i', [$id]);
    hapus_gambar($kamar['foto']);
    catat_aksi($conn, $user['id'], 'KAMAR_HAPUS', 'room_types', $id, ['nama' => $kamar['nama']]);
    json_ok(['id' => $id, 'dihapus' => true]);
}

// ---------------- POST: tambah / ubah ----------------
$lama = null;
if ($id) {
    $lama = db_satu($conn, 'SELECT * FROM room_types WHERE id = ?', 'i', [$id]) ?? json_error('Kamar tidak ditemukan.', 404);
}

$data = [
    'category_id'     => input_int('category_id'),
    'nama'            => input_teks('nama'),
    'deskripsi'       => input_teks('deskripsi'),
    'kapasitas'       => input_int('kapasitas', -1),
    'total_stok'      => input_int('total_stok', -1),
    'tarif_per_malam' => input_int('tarif_per_malam', -1),
    'fasilitas'       => input_teks('fasilitas'),
    'is_active'       => array_key_exists('is_active', input()) ? (int) input_bool('is_active') : 1,
];

$errors = [];
if (!db_satu($conn, 'SELECT id FROM categories WHERE id = ?', 'i', [$data['category_id']])) $errors['category_id'] = 'Pilih kategori.';
if (mb_strlen($data['nama']) < 3 || mb_strlen($data['nama']) > 100) $errors['nama'] = 'Nama 3–100 karakter.';
if (mb_strlen($data['deskripsi']) < 10) $errors['deskripsi'] = 'Deskripsi minimal 10 karakter.';
if ($data['kapasitas'] < 1 || $data['kapasitas'] > 20) $errors['kapasitas'] = 'Kapasitas 1–20 tamu.';
if ($data['total_stok'] < 0 || $data['total_stok'] > 500) $errors['total_stok'] = 'Stok 0–500 unit.';
if ($data['tarif_per_malam'] < 1000) $errors['tarif_per_malam'] = 'Tarif minimal Rp1.000.';
if (mb_strlen($data['fasilitas']) > 500) $errors['fasilitas'] = 'Fasilitas maksimal 500 karakter.';
if ($errors) json_error('Periksa kembali data kamar.', 422, $errors);

// Foto (opsional)
$foto = $lama['foto'] ?? null;
if (!empty($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
    try {
        $foto_baru = simpan_gambar($_FILES['foto']);
    } catch (InvalidArgumentException $e) {
        json_error($e->getMessage(), 422, ['foto' => $e->getMessage()]);
    }
    hapus_gambar($foto);
    $foto = $foto_baru;
} elseif (input_bool('hapus_foto')) {
    hapus_gambar($foto);
    $foto = null;
}

$param = [$data['category_id'], $data['nama'], $data['deskripsi'], $data['kapasitas'], $data['total_stok'],
          $data['tarif_per_malam'], $data['fasilitas'], $foto, $data['is_active']];

if ($lama) {
    db_jalankan($conn,
        'UPDATE room_types SET category_id = ?, nama = ?, deskripsi = ?, kapasitas = ?, total_stok = ?, tarif_per_malam = ?,
                               fasilitas = ?, foto = ?, is_active = ? WHERE id = ?',
        'issiiissii', [...$param, $id]);
    catat_aksi($conn, $user['id'], 'KAMAR_UBAH', 'room_types', $id, [
        'nama'  => $data['nama'],
        'stok'  => [(int) $lama['total_stok'], $data['total_stok']],
        'tarif' => [(int) $lama['tarif_per_malam'], $data['tarif_per_malam']],
    ]);
    $kode = 200;
} else {
    db_jalankan($conn,
        'INSERT INTO room_types (category_id, nama, deskripsi, kapasitas, total_stok, tarif_per_malam, fasilitas, foto, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        'issiiissi', $param);
    $id = mysqli_insert_id($conn);
    catat_aksi($conn, $user['id'], 'KAMAR_TAMBAH', 'room_types', $id, ['nama' => $data['nama']]);
    $kode = 201;
}

json_ok(format_kamar(db_satu($conn, $sql_dasar . ' WHERE r.id = ?', 'i', [$id])), $kode);
