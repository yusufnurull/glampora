<?php
/** PENUTUP LAYOUT DASHBOARD — Penanggung jawab: FRONTEND 2 (variabel $scripts & $scripts_cdn sama seperti footer.php) */
$scripts     = $scripts ?? [];
$scripts_cdn = $scripts_cdn ?? [];
?>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php foreach ($scripts_cdn as $src): ?>
<script src="<?= e($src) ?>"></script>
<?php endforeach; ?>
<script src="<?= url('assets/js/app.js') ?>"></script>
<?php foreach ($scripts as $src): ?>
<script src="<?= url($src) ?>"></script>
<?php endforeach; ?>
</body>
</html>
