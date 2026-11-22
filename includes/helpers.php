<?php
/**
 * FUNGSI BANTU UMUM — Penanggung jawab: BACKEND 1
 */

/** Escape output HTML untuk mencegah XSS. Gunakan di setiap echo data dari database/user. */
function e($teks): string
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

/** Buat URL lengkap dari path relatif terhadap root aplikasi. url('login.php') -> /glampora/login.php */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** URL foto/cover yang diunggah. */
function url_upload(?string $nama_file, string $default = 'assets/img/placeholder.svg'): string
{
    return $nama_file ? url('assets/uploads/' . $nama_file) : url($default);
}

function rupiah(int $angka): string
{
    return 'Rp' . number_format($angka, 0, ',', '.');
}

/** Ubah judul menjadi slug: "Tips Memilih Tenda" -> "tips-memilih-tenda" */
function slugify(string $teks): string
{
    $slug = strtolower(trim($teks));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-') ?: 'artikel';
}

/** Format tanggal Indonesia: 2026-12-10 -> 10 Des 2026 */
function tanggal_id(?string $tanggal, bool $dengan_jam = false): string
{
    if (!$tanggal) return '-';
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($tanggal);
    $hasil = date('j', $t) . ' ' . $bulan[(int) date('n', $t)] . ' ' . date('Y', $t);
    return $dengan_jam ? $hasil . ', ' . date('H:i', $t) : $hasil;
}

/** Validasi tanggal berformat YYYY-MM-DD. */
function tanggal_valid(?string $tanggal): bool
{
    if (!$tanggal) return false;
    $d = DateTime::createFromFormat('Y-m-d', $tanggal);
    return $d && $d->format('Y-m-d') === $tanggal;
}

/** Validasi nomor HP Indonesia: 08xxxxxxxx atau +628xxxxxxxx */
function telepon_valid(string $telepon): bool
{
    return (bool) preg_match('/^(\+62|0)8[0-9]{7,12}$/', $telepon);
}

/** Ambil semua baris hasil prepared statement sebagai array asosiatif. */
function db_semua(mysqli $conn, string $sql, string $tipe = '', array $param = []): array
{
    $stmt = mysqli_prepare($conn, $sql);
    if ($tipe !== '') mysqli_stmt_bind_param($stmt, $tipe, ...$param);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}

/** Ambil satu baris (atau null). */
function db_satu(mysqli $conn, string $sql, string $tipe = '', array $param = []): ?array
{
    $baris = db_semua($conn, $sql, $tipe, $param);
    return $baris[0] ?? null;
}

/** Jalankan INSERT/UPDATE/DELETE. Mengembalikan jumlah baris terpengaruh. */
function db_jalankan(mysqli $conn, string $sql, string $tipe = '', array $param = []): int
{
    $stmt = mysqli_prepare($conn, $sql);
    if ($tipe !== '') mysqli_stmt_bind_param($stmt, $tipe, ...$param);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_affected_rows($stmt);
}

/**
 * Simpan file upload gambar dengan aman (Modul 10).
 * Mengembalikan nama file baru, atau melempar Exception berisi pesan untuk user.
 */
function simpan_gambar(array $file, int $maks_mb = 2): string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Upload gambar gagal (kode ' . $file['error'] . ').');
    }
    if ($file['size'] > $maks_mb * 1024 * 1024) {
        throw new InvalidArgumentException("Ukuran gambar maksimal {$maks_mb} MB.");
    }
    $izin = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = mime_content_type($file['tmp_name']);   // cek isi file, bukan hanya ekstensi
    if (!isset($izin[$mime])) {
        throw new InvalidArgumentException('Format gambar harus JPG, PNG, atau WEBP.');
    }
    $nama_baru = date('Ymd') . '_' . bin2hex(random_bytes(6)) . '.' . $izin[$mime];
    $tujuan = __DIR__ . '/../assets/uploads/' . $nama_baru;
    if (!move_uploaded_file($file['tmp_name'], $tujuan)) {
        throw new RuntimeException('Gagal menyimpan gambar.');
    }
    return $nama_baru;
}

/** Hapus file upload lama (dipakai saat gambar diganti/dihapus). */
function hapus_gambar(?string $nama_file): void
{
    if ($nama_file && preg_match('/^[\w.-]+$/', $nama_file)) {
        $path = __DIR__ . '/../assets/uploads/' . $nama_file;
        if (is_file($path)) unlink($path);
    }
}
