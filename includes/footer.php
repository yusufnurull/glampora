<?php
/**
 * FOOTER HALAMAN USER — Penanggung jawab: FRONTEND 2
 *
 * Script tambahan per halaman:
 *   $scripts = ['assets/js/reservasi.js'];                  // file lokal
 *   $scripts_cdn = ['https://cdn.jsdelivr.net/...'];         // library dari 
CDN
 */
$scripts = $scripts ?? [];
$scripts_cdn = $scripts_cdn ?? [];
?>
</main>
<footer class="footer-situs mt-5">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="d-flex align-items-center gap-2 mb-2 fw
semibold">
                    <img src="<?= url('assets/img/logo.svg') ?>" alt="" 
width="28" height="28"> Lembah Kabut Glamping
                </div>
                <p class="mb-0 opacity-75">Menginap nyaman di tengah alam: 
tenda safari, dome, dan kabin hutan.</p>
            </div>
            <div class="col-6 col-md-3">
                <h2 class="h6">Jelajahi</h2>
                <ul class="list-unstyled mb-0">
                    <li><a href="<?= url('reservasi.php') ?>">Reservasi</a>

</li>
                    <li><a href="<?= url('checkin.php') ?>">Check-in</a></li>
                    <li><a href="<?= url('artikel.php') ?>">Artikel</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-4">
                <h2 class="h6">Kontak</h2>
                <ul class="list-unstyled mb-0">
                    <li><i class="bi bi-geo-alt" aria-hidden="true"></i> Jl. 
Pinus Raya No. 8, Lembang</li>
                    <li><i class="bi bi-whatsapp" aria-hidden="true"></i> 
0812-0000-1234</li>
                    <li><i class="bi bi-envelope" aria-hidden="true"></i> 
halo@lembahkabut.test</li>
                </ul>
            </div>
        </div>
        <p class="small opacity-50 mt-4 mb-0">© <?= date('Y') ?> Lembah Kabut 
Glamping · Proyek tugas akhir</p>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js">
</script>
<script 
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.mi
n.js"></script>
<?php foreach ($scripts_cdn as $src): ?>
<script src="<?= e($src) ?>"></script>
<?php endforeach; ?>
<script src="<?= url('assets/js/app.js') ?>"></script>
<?php foreach ($scripts as $src): ?>
<script src="<?= url($src) ?>"></script>
<?php endforeach; ?>
</body>
</html>
