<?php
declare(strict_types=1);
?>
<section class="max-w-2xl">
    <h1 class="text-2xl font-bold mb-4 text-red-400">
        <?= e($statusCode ?? 404) ?> - <?= e($message ?? 'Not found') ?>
    </h1>
    <p class="text-gray-300 mb-2">Path: <code class="text-gray-100"><?= e($requestedPath ?? '/') ?></code></p>
    <a class="text-blue-400 hover:text-blue-300" href="<?= e($app['routes']['users'] ?? '/users') ?>">Kembali ke halaman utama</a>
</section>
