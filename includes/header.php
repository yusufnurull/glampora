<?php
/**
 * HEADER + NAVBAR HALAMAN USER — Penanggung jawab: FRONTEND 2
 *
 * Pemakaian di halaman (setelah require bootstrap.php):
 *   $judul = 'Reservasi';        // judul tab browser
 *   $menu  = 'reservasi';        // menu yang aktif: 
beranda|reservasi|checkin|artikel
 *   require __DIR__ . '/includes/header.php';
 */
$judul = $judul ?? 'Glamping';
$menu = $menu ?? '';
$user_nav = user_login();
$daftar_menu = [
    'beranda'   => ['Beranda', 'index.php'],
    'reservasi' => ['Reservasi', 'reservasi.php'],
    'checkin'   => ['Check-in', 'checkin.php'],
    'artikel'   => ['Artikel', 'artikel.php'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul) ?> · Lembah Kabut Glamping</title>
    <link rel="icon" href="<?= url('assets/img/logo.svg') ?>">
    <link 
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css
" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap
icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body data-base-url="<?= e(BASE_URL) ?>">
<a class="visually-hidden-focusable skip-link" href="#konten">Lewati ke 
konten</a>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-semibold" 
href="<?= url('index.php') ?>">
            <img src="<?= url('assets/img/logo.svg') ?>" alt="" width="32"

height="32"> Lembah Kabut
        </a>
        <button class="navbar-toggler" type="button" data-bs
toggle="collapse" data-bs-target="#navUtama"
                aria-controls="navUtama" aria-expanded="false" aria
label="Buka menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navUtama">
            <ul class="navbar-nav me-auto">
                <?php foreach ($daftar_menu as $kunci => [$label, $href]): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $menu === $kunci ? 'active' : 
'' ?>" href="<?= url($href) ?>"
                           <?= $menu === $kunci ? 'aria-current="page"' : '' 
?>><?= $label ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="d-flex gap-2 align-items-lg-center flex-column flex
lg-row pb-3 pb-lg-0">
                <?php if ($user_nav): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown
toggle w-100" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle" aria
hidden="true"></i> <?= e($user_nav['nama']) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if (is_staff($user_nav)): ?>
                                <li><a class="dropdown-item" href="<?= 
url('dashboard/index.php') ?>"><i class="bi bi-speedometer2"></i> 
Dashboard</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="<?= 
url('profil.php') ?>"><i class="bi bi-person"></i> Profil & Riwayat</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?
= url('logout.php') ?>"><i class="bi bi-box-arrow-right"></i> Keluar</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="btn btn-outline-primary" href="<?=

url('login.php') ?>">Masuk</a>
                    <a class="btn btn-primary" href="<?= url('register.php') 
?>">Daftar</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<main id="konten">
    
