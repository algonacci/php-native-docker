<?php
declare(strict_types=1);
?>
<section>
    <div class="alert alert-danger" role="alert">
        <h1 class="h4 mb-2"><?= e($statusCode ?? 404) ?> - <?= e($message ?? 'Not found') ?></h1>
        <p class="mb-0">Path: <code><?= e($requestedPath ?? '/') ?></code></p>
    </div>
    <a class="btn btn-outline-primary btn-sm" href="<?= e($app['routes']['users'] ?? '/users') ?>">Kembali ke halaman utama</a>
</section>
