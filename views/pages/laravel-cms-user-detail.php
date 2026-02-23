<?php
declare(strict_types=1);
?>
<section>
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h1 class="h4 mb-4">Laravel CMS User Detail</h1>
            <dl class="row mb-0">
                <dt class="col-sm-3 text-body-secondary">ID</dt>
                <dd class="col-sm-9 font-monospace">#<?= e($laravelCmsUser['id'] ?? '-') ?></dd>
                <dt class="col-sm-3 text-body-secondary">Name</dt>
                <dd class="col-sm-9"><?= e($laravelCmsUser['name'] ?? '-') ?></dd>
                <dt class="col-sm-3 text-body-secondary">Email</dt>
                <dd class="col-sm-9"><?= e($laravelCmsUser['email'] ?? '-') ?></dd>
            </dl>
            <a class="btn btn-outline-primary btn-sm mt-4" href="/laravel-cms-users">Kembali ke Laravel CMS Users</a>
        </div>
    </div>
</section>
