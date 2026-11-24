<?php
/**
 * LAYOUT DASHBOARD (SIDEBAR) — Penanggung jawab: FRONTEND 2
 *
 * Pemakaian di halaman dashboard:
 *   $user  = cek_role(['ADMIN', 'STAFF']);
 *   $judul = 'Kelola Kamar';
 *   $menu  = 'kamar';
 *   require __DIR__ . '/../includes/dashboard_header.php';
 */
$judul = $judul ?? 'Dashboard';
$menu  = $menu ?? '';
$menu_dashboard = [
    // kunci => [label, ikon, file, role yang boleh]
    'ringkasan'  => ['Ringkasan', 'speedometer2', 'index.php', ['ADMIN', 'STAFF']],
    'pembatalan' => ['Pembatalan', 'x-octagon', 'pembatalan.php', ['ADMIN', 'STAFF']],
    'kamar'      => ['Jenis Kamar', 'house-door', 'kamar.php', ['ADMIN', 'STAFF']],
    'artikel'    => ['Artikel', 'journal-text', 'artikel.php', ['ADMIN', 'STAFF']],
    'akun'       => ['Manajemen Akun', 'people', 'akun.php', ['ADMIN']],
    'audit'      => ['Catatan Tindakan', 'clock-history', 'audit_log.php', ['ADMIN']],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul) ?> · Dashboard Glampora</title>
    <link rel="icon" href="<?= url('assets/img/logo.svg') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="dashboard" data-base-url="<?= e(BASE_URL) ?>" data-role="<?= e($user['role']) ?>">
<a class="visually-hidden-focusable skip-link" href="#konten">Lewati ke konten</a>

<!-- Bar atas (hanya tampil di HP) -->
<header class="navbar bg-white border-bottom d-lg-none px-3 sticky-top">
    <button class="btn btn-outline-secondary" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Buka menu">
        <i class="bi bi-list" aria-hidden="true"></i>
    </button>
    <span class="fw-semibold"><?= e($judul) ?></span>
    <span style="width: 42px"></span>
</header>

<div class="d-flex">
    <aside class="offcanvas-lg offcanvas-start sidebar" tabindex="-1" id="sidebar" aria-label="Menu dashboard">
        <div class="offcanvas-header">
            <span class="fw-semibold">Menu</span>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Tutup"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column p-3">
            <a href="<?= url('dashboard/index.php') ?>" class="d-flex align-items-center gap-2 mb-4 text-white text-decoration-none fw-semibold">
                <img src="<?= url('assets/img/logo.svg') ?>" alt="" width="30" height="30"> Glampora
            </a>
            <nav class="nav nav-pills flex-column gap-1 mb-auto">
                <?php foreach ($menu_dashboard as $kunci => [$label, $ikon, $file, $roles]):
                    if (!in_array($user['role'], $roles, true)) continue; ?>
                    <a href="<?= url('dashboard/' . $file) ?>" class="nav-link <?= $menu === $kunci ? 'active' : '' ?>"
                       <?= $menu === $kunci ? 'aria-current="page"' : '' ?>>
                        <i class="bi bi-<?= $ikon ?> me-2" aria-hidden="true"></i><?= $label ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <hr class="border-secondary">
            <div class="small text-white-50 mb-2">
                <i class="bi bi-person-circle" aria-hidden="true"></i> <?= e($user['nama']) ?>
                <span class="badge text-bg-light ms-1"><?= e($user['role']) ?></span>
            </div>
            <a href="<?= url('index.php') ?>" class="nav-link px-0 text-white-50"><i class="bi bi-globe me-2" aria-hidden="true"></i>Lihat situs</a>
            <a href="<?= url('logout.php') ?>" class="nav-link px-0 text-white-50"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Keluar</a>
        </div>
    </aside>

    <main id="konten" class="flex-grow-1 p-3 p-lg-4 konten-dashboard">
        <h1 class="h3 mb-4 d-none d-lg-block"><?= e($judul) ?></h1>
