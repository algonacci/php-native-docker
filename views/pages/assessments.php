<?php
declare(strict_types=1);
?>
<section>
    <h1 class="text-2xl font-bold mb-6 text-blue-400">Daftar Assessment</h1>

    <?php partial('partials/error-alert', ['error' => $error ?? null]); ?>

    <div class="bg-gray-800 rounded-lg shadow-xl overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-700 text-gray-300">
                <tr>
                    <th class="p-4">ID</th>
                    <th class="p-4">Title</th>
                    <th class="p-4">Deskripsi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                <?php if (!empty($assessments)): ?>
                    <?php foreach ($assessments as $assessment): ?>
                        <tr class="hover:bg-gray-700/50">
                            <td class="p-4 font-mono text-sm text-blue-300">
                                <a class="hover:text-blue-200" href="/assessments/<?= e($assessment['id']) ?>">#<?= e($assessment['id']) ?></a>
                            </td>
                            <td class="p-4 text-gray-200"><?= e($assessment['title']) ?></td>
                            <td class="p-4 text-gray-400"><?= e($assessment['description'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="p-10 text-center text-gray-500">Belum ada data assessment.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
