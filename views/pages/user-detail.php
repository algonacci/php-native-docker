<?php
declare(strict_types=1);
?>
<section>
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h1 class="h4 mb-4">User Detail</h1>
            <dl class="row mb-0">
                <dt class="col-sm-3 text-body-secondary">ID</dt>
                <dd class="col-sm-9 font-monospace">#<?= e($user['id'] ?? '-') ?></dd>
                <dt class="col-sm-3 text-body-secondary">Email</dt>
                <dd class="col-sm-9"><?= e($user['email'] ?? '-') ?></dd>
            </dl>
            <a class="btn btn-outline-primary btn-sm mt-4" href="<?= e($app['routes']['users'] ?? '/users') ?>">Kembali ke users</a>
        </div>
    </div>
</section>
