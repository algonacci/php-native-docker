<?php
declare(strict_types=1);
?>
<section>
    <h1 class="text-2xl font-bold mb-6 text-blue-400">Daftar Laravel CMS Users</h1>

    <?php partial('partials/error-alert', ['error' => $error ?? null]); ?>

    <div class="bg-gray-800 rounded-lg shadow-xl overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-700 text-gray-300">
                <tr>
                    <th class="p-4">ID</th>
                    <th class="p-4">Name</th>
                    <th class="p-4">Email</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                <?php if (!empty($laravelCmsUsers)): ?>
                    <?php foreach ($laravelCmsUsers as $laravelCmsUser): ?>
                        <tr class="hover:bg-gray-700/50">
                            <td class="p-4 font-mono text-sm text-blue-300">
                                <a class="hover:text-blue-200" href="/laravel-cms-users/<?= e($laravelCmsUser['id']) ?>">#<?= e($laravelCmsUser['id']) ?></a>
                            </td>
                            <td class="p-4 text-gray-200"><?= e($laravelCmsUser['name']) ?></td>
                            <td class="p-4 text-gray-400"><?= e($laravelCmsUser['email']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="p-10 text-center text-gray-500">Belum ada data Laravel CMS user.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
