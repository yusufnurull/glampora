<?php
/**
 * API CATATAN TINDAKAN STAFF — Penanggung jawab: BACKEND 1 (khusus ADMIN)
 *
 * GET api/audit.php?user_id=2&dari=2026-11-01&sampai=2026-11-30&halaman=1
 */
require_once __DIR__ . '/../includes/bootstrap.php';

api_mulai(['GET']);
api_butuh_role(['ADMIN']);

$where = ' WHERE 1 = 1';
$tipe = '';
$param = [];

if ($uid = query_int('user_id')) {
    $where .= ' AND l.user_id = ?';
    $tipe .= 'i';
    $param[] = $uid;
}
if (tanggal_valid($_GET['dari'] ?? null)) {
    $where .= ' AND l.created_at >= ?';
    $tipe .= 's';
    $param[] = $_GET['dari'] . ' 00:00:00';
}
if (tanggal_valid($_GET['sampai'] ?? null)) {
    $where .= ' AND l.created_at <= ?';
    $tipe .= 's';
    $param[] = $_GET['sampai'] . ' 23:59:59';
}

$per_halaman = 25;
$halaman = max(1, query_int('halaman'));
$total = (int) db_satu($conn, 'SELECT COUNT(*) AS n FROM audit_logs l' . $where, $tipe, $param)['n'];

$baris = db_semua($conn,
    'SELECT l.*, u.nama, u.role FROM audit_logs l LEFT JOIN users u ON u.id = l.user_id' . $where .
    ' ORDER BY l.created_at DESC, l.id DESC LIMIT ' . $per_halaman . ' OFFSET ' . (($halaman - 1) * $per_halaman),
    $tipe, $param);

json_ok([
    'total'          => $total,
    'halaman'        => $halaman,
    'jumlah_halaman' => max(1, (int) ceil($total / $per_halaman)),
    'staff'          => db_semua($conn, "SELECT id, nama, role FROM users WHERE role IN ('ADMIN', 'STAFF') ORDER BY nama"),
    'log'            => array_map(fn($l) => [
        'id'         => (int) $l['id'],
        'waktu'      => tanggal_id($l['created_at'], true),
        'nama'       => $l['nama'] ?? '(akun dihapus)',
        'role'       => $l['role'],
        'aksi'       => $l['aksi'],
        'entitas'    => $l['entitas'],
        'entitas_id' => $l['entitas_id'] !== null ? (int) $l['entitas_id'] : null,
        'detail'     => $l['detail'] ? json_decode($l['detail'], true) : null,
    ], $baris),
]);
