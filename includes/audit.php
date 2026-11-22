<?php
/**
 * CATATAN TINDAKAN STAFF (AUDIT LOG) — Penanggung jawab: BACKEND 1
 *
 * Panggil setiap kali staff/admin mengubah data. Contoh:
 *   catat_aksi($conn, $user['id'], 'KAMAR_UBAH', 'room_types', $id, ['nama' => $nama]);
 *
 * Daftar kode aksi yang dipakai:
 *   KAMAR_TAMBAH, KAMAR_UBAH, KAMAR_HAPUS, KAMAR_NONAKTIF
 *   KATEGORI_TAMBAH, KATEGORI_UBAH, KATEGORI_HAPUS
 *   ARTIKEL_TAMBAH, ARTIKEL_UBAH, ARTIKEL_HAPUS
 *   PEMBATALAN_TERIMA, PEMBATALAN_TOLAK
 *   AKUN_TAMBAH, AKUN_UBAH
 */
function catat_aksi(mysqli $conn, int $user_id, string $aksi, string $entitas, ?int $entitas_id = null, array $detail = []): void
{
    db_jalankan(
        $conn,
        'INSERT INTO audit_logs (user_id, aksi, entitas, entitas_id, detail) VALUES (?, ?, ?, ?, ?)',
        'issis',
        [$user_id, $aksi, $entitas, $entitas_id, $detail ? json_encode($detail, JSON_UNESCAPED_UNICODE) : null]
    );
}
