<?php
declare(strict_types=1);
?>
<section class="max-w-2xl bg-gray-800 rounded-lg shadow-xl p-6">
    <h1 class="text-2xl font-bold mb-4 text-blue-400">Laravel CMS User Detail</h1>
    <dl class="space-y-3">
        <div>
            <dt class="text-gray-400 text-sm">ID</dt>
            <dd class="text-white font-mono">#<?= e($laravelCmsUser['id'] ?? '-') ?></dd>
        </div>
        <div>
            <dt class="text-gray-400 text-sm">Name</dt>
            <dd class="text-white"><?= e($laravelCmsUser['name'] ?? '-') ?></dd>
        </div>
        <div>
            <dt class="text-gray-400 text-sm">Email</dt>
            <dd class="text-white"><?= e($laravelCmsUser['email'] ?? '-') ?></dd>
        </div>
    </dl>
    <a class="inline-block mt-6 text-blue-400 hover:text-blue-300" href="/laravel-cms-users">Kembali ke Laravel CMS Users</a>
</section>
