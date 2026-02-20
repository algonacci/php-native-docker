<?php
declare(strict_types=1);
?>
<section class="max-w-2xl bg-gray-800 rounded-lg shadow-xl p-6">
    <h1 class="text-2xl font-bold mb-4 text-blue-400">Assessment Detail</h1>
    <dl class="space-y-3">
        <div>
            <dt class="text-gray-400 text-sm">ID</dt>
            <dd class="text-white font-mono">#<?= e($assessment['id'] ?? '-') ?></dd>
        </div>
        <div>
            <dt class="text-gray-400 text-sm">Title</dt>
            <dd class="text-white"><?= e($assessment['title'] ?? '-') ?></dd>
        </div>
        <div>
            <dt class="text-gray-400 text-sm">Description</dt>
            <dd class="text-white"><?= e($assessment['description'] ?? '-') ?></dd>
        </div>
    </dl>
    <a class="inline-block mt-6 text-blue-400 hover:text-blue-300" href="<?= e($app['routes']['assessments'] ?? '/assessments') ?>">Kembali ke assessments</a>
</section>
