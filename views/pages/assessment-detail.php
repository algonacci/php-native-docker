<?php
declare(strict_types=1);
?>
<section>
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h1 class="h4 mb-4">Assessment Detail</h1>
            <dl class="row mb-0">
                <dt class="col-sm-3 text-body-secondary">ID</dt>
                <dd class="col-sm-9 font-monospace">#<?= e($assessment['id'] ?? '-') ?></dd>
                <dt class="col-sm-3 text-body-secondary">Title</dt>
                <dd class="col-sm-9"><?= e($assessment['title'] ?? '-') ?></dd>
                <dt class="col-sm-3 text-body-secondary">Description</dt>
                <dd class="col-sm-9"><?= e($assessment['description'] ?? '-') ?></dd>
            </dl>
            <a class="btn btn-outline-primary btn-sm mt-4" href="<?= e($app['routes']['assessments'] ?? '/assessments') ?>">Kembali ke assessments</a>
        </div>
    </div>
</section>
