<?php
/** HALAMAN AKSES DITOLAK — Penanggung jawab: FRONTEND 2 (dipanggil otomatis oleh cek_role()) */
require_once __DIR__ . '/includes/bootstrap.php';
$judul = 'Akses Ditolak';
require __DIR__ . '/includes/header.php';
?>
<section class="container py-5 text-center">
    <i class="bi bi-shield-lock display-1 text-secondary" aria-hidden="true"></i>
    <h1 class="h3 mt-3">Akses ditolak</h1>
    <p class="text-secondary">Akun Anda tidak memiliki izin untuk membuka halaman ini.</p>
    <a href="<?= url('index.php') ?>" class="btn btn-primary">Kembali ke beranda</a>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
